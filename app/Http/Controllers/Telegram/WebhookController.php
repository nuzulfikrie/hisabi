<?php

namespace App\Http\Controllers\Telegram;

use App\Actions\Telegram\CreateTransactionFromMessage;
use App\Actions\Telegram\DownloadTelegramFile;
use App\Actions\Telegram\LinkTelegramAccount;
use App\Actions\Telegram\ParseTransactionMessage;
use App\Actions\Telegram\ProcessReceiptImage;
use App\Actions\Telegram\SendTransactionConfirmation;
use App\Domains\Transaction\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Models\TelegramTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    /**
     * Handle incoming Telegram webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            Log::info('Telegram webhook received', ['update_id' => $update['update_id'] ?? null]);

            // Handle message
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

            // Handle callback queries (inline buttons)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still return ok to prevent Telegram from retrying
            return response()->json(['status' => 'ok']);
        }
    }

    private function handleMessage(array $message): void
    {
        $chatId = (string) $message['chat']['id'];
        $messageId = (string) $message['message_id'];
        $text = $message['text'] ?? '';
        $username = $message['from']['username'] ?? null;

        // Check if this is a photo/receipt
        if (isset($message['photo']) && ! empty($message['photo'])) {
            $this->handleReceiptPhoto($message, $chatId, $messageId, $username);
            return;
        }

        // Store raw message
        $telegramTransaction = TelegramTransaction::create([
            'telegram_chat_id' => $chatId,
            'telegram_message_id' => $messageId,
            'raw_message' => $text,
            'status' => 'pending',
        ]);

        // Handle bot commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $chatId, $username, $telegramTransaction);
            return;
        }

        // Find linked user
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessageSafe($chatId, "Please link your account first. Use /link command to get started.");
            $telegramTransaction->update(['status' => 'ignored']);

            return;
        }

        $telegramTransaction->update(['user_id' => $user->id]);

        try {
            // Parse message using Laravel Action
            $parsedData = ParseTransactionMessage::run($text, $chatId, $messageId);

            // Update with parsed data
            $telegramTransaction->update(['parsed_data' => $parsedData]);

            // Create transaction using Laravel Action
            $transaction = CreateTransactionFromMessage::run($telegramTransaction, $parsedData);

            // Send confirmation
            SendTransactionConfirmation::run($transaction);
        } catch (\Exception $e) {
            $telegramTransaction->markAsFailed($e->getMessage());

            $this->sendMessageSafe($chatId, "❌ Could not process your message. Please use format:\n\nexpense 50 lunch\nor\nincome 1000 salary");
        }
    }

    /**
     * Handle receipt photo with OCR
     */
    private function handleReceiptPhoto(array $message, string $chatId, string $messageId, ?string $username): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessageSafe($chatId, "Please link your account first. Use /link command to get started.");
            return;
        }

        // Send "processing" message
        $processingMsg = null;
        try {
            $processingMsg = Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "🔄 Processing receipt...",
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send processing message', ['error' => $e->getMessage()]);
        }

        $localFilePath = null;

        try {
            // Get the largest photo (best quality)
            $photos = $message['photo'];
            $largestPhoto = end($photos);
            $fileId = $largestPhoto['file_id'];

            // Download file from Telegram
            $localFilePath = DownloadTelegramFile::run($fileId);

            // Process with OCR
            $ocrResult = ProcessReceiptImage::run($localFilePath, $chatId, $messageId);

            if (! $ocrResult['success']) {
                throw new \RuntimeException('OCR processing failed');
            }

            // Store the OCR result
            $telegramTransaction = TelegramTransaction::create([
                'telegram_chat_id' => $chatId,
                'telegram_message_id' => $messageId,
                'raw_message' => $ocrResult['text'],
                'parsed_data' => $ocrResult['parsed_data'],
                'status' => 'processed',
                'user_id' => $user->id,
            ]);

            // Format response
            $parsed = $ocrResult['parsed_data'];
            $merchant = $parsed['merchant'] ?? 'Unknown';
            $amount = $parsed['amount'] ? number_format($parsed['amount'], 2) : 'Not found';
            $date = $parsed['date'] ?? 'Not found';
            $engine = $ocrResult['engine'];
            $currency = config('hisabi.currency', 'MYR');

            // Delete processing message
            if ($processingMsg) {
                try {
                    Telegram::deleteMessage([
                        'chat_id' => $chatId,
                        'message_id' => $processingMsg->get('message_id'),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete processing message', ['error' => $e->getMessage()]);
                }
            }

            // Send result
            $response = "<b>🧾 Receipt Processed</b>\n\n";
            $response .= "<b>Merchant:</b> {$merchant}\n";
            $response .= "<b>Amount:</b> {$currency} {$amount}\n";
            $response .= "<b>Date:</b> {$date}\n";
            $response .= "<b>OCR Engine:</b> {$engine}\n\n";
            $response .= "<i>To save this as a transaction, reply with:</i>\n";
            $response .= "<code>expense {$amount} {$merchant}</code>";

            $this->sendMessageSafe($chatId, $response, true);

        } catch (\Exception $e) {
            Log::error('Receipt processing failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            // Delete processing message
            if ($processingMsg) {
                try {
                    Telegram::deleteMessage([
                        'chat_id' => $chatId,
                        'message_id' => $processingMsg->get('message_id'),
                    ]);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }

            $this->sendMessageSafe($chatId, "❌ Sorry, I couldn't process that receipt.\n\nError: {$e->getMessage()}\n\nPlease try:\n• Sending a clearer photo\n• Or type the details manually");
        } finally {
            // Cleanup temp file
            if ($localFilePath) {
                DownloadTelegramFile::cleanup($localFilePath);
            }
        }
    }

    private function handleCommand(string $text, string $chatId, ?string $username, TelegramTransaction $telegramTransaction): void
    {
        $parts = explode(' ', $text);
        $command = $parts[0];
        $args = array_slice($parts, 1);

        match ($command) {
            '/start' => $this->handleStartCommand($chatId, $telegramTransaction),
            '/help' => $this->handleHelpCommand($chatId, $telegramTransaction),
            '/link' => $this->handleLinkCommand($chatId, $username, $args, $telegramTransaction),
            '/stats' => $this->handleStatsCommand($chatId, $telegramTransaction),
            '/status' => $this->handleStatusCommand($chatId, $telegramTransaction),
            '/logout' => $this->handleLogoutCommand($chatId, $telegramTransaction),
            '/ocr' => $this->handleOcrStatusCommand($chatId, $telegramTransaction),
            default => $this->handleUnknownCommand($chatId, $telegramTransaction),
        };
    }

    private function handleStartCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        $this->sendMessageSafe(
            $chatId,
            "Welcome to Hisabi Bot! 🤖\n\nI can help you track expenses and income.\n\n<b>Text Input:</b>\n• expense 50 lunch\n• income 1000 salary\n• -25 coffee\n• +500 freelance\n\n<b>Receipt Photos:</b>\nJust send a photo of your receipt and I'll extract the details!\n\nGet started with /link",
            true
        );
        $telegramTransaction->update(['status' => 'ignored']);
    }

    private function handleHelpCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        $this->sendMessageSafe(
            $chatId,
            "<b>Hisabi Bot Commands</b>\n\n/link <code> - Link your account with OTP\n/status - View your account status\n/stats - View your stats\n/ocr - Check OCR service status\n/logout - Unlink your account\n/help - Show this help\n\n<b>Text Formats:</b>\n• expense [amount] [description]\n• income [amount] [description]\n• -[amount] [description]\n• +[amount] [description]\n\n<b>Receipt Scanning:</b>\nSimply send a photo of any receipt!",
            true
        );
        $telegramTransaction->update(['status' => 'ignored']);
    }

    private function handleLinkCommand(string $chatId, ?string $username, array $args, TelegramTransaction $telegramTransaction): void
    {
        // Check if OTP was provided
        if (empty($args[0])) {
            $this->sendMessageSafe(
                $chatId,
                "❌ Please provide a verification code.\n\nUsage: /link <code>\n\nExample: /link 123456\n\nGet your code from Settings → Telegram in the web app."
            );
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        $code = trim($args[0]);

        // Validate OTP format (6-digit numeric)
        if (! preg_match('/^\d{6}$/', $code)) {
            $this->sendMessageSafe(
                $chatId,
                "❌ Invalid code format.\n\nThe code must be a 6-digit number.\n\nExample: /link 123456"
            );
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        // Check if already linked
        $existingUser = User::where('telegram_chat_id', $chatId)->first();
        if ($existingUser) {
            $this->sendMessageSafe(
                $chatId,
                "✅ Your Telegram account is already linked to user: {$existingUser->name}\n\nUse /status to see your account info or /logout to unlink."
            );
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        // Try to link using the LinkTelegramAccount action
        $user = LinkTelegramAccount::run($chatId, $username, $code);

        if (! $user) {
            $this->sendMessageSafe(
                $chatId,
                "❌ Invalid or expired verification code.\n\nPlease generate a new code from the web app (Settings → Telegram) and try again.\n\nCode expires in 10 minutes."
            );
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        // Update transaction with user
        $telegramTransaction->update([
            'user_id' => $user->id,
            'status' => 'processed',
        ]);

        $this->sendMessageSafe(
            $chatId,
            "✅ <b>Account Linked Successfully!</b>\n\nWelcome, {$user->name}!\n\nYou can now:\n• Send expense/income messages\n• Upload receipt photos\n• Use /stats to see your summary\n• Use /status to check your account\n\nTry sending: expense 50 lunch",
            true
        );
    }

    private function handleStatsCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessageSafe($chatId, "Please link your account first with /link");
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        // Fixed N+1 query - eager load relationships
        $transactions = Transaction::with('brand.category')
            ->where('user_id', $user->id)
            ->get();

        $income = $transactions->filter(fn ($t) => $t->brand?->category?->type === 'INCOME')->sum('amount');
        $expense = $transactions->filter(fn ($t) => $t->brand?->category?->type === 'EXPENSES')->sum('amount');
        $transactionCount = $transactions->count();

        $currency = config('hisabi.currency', 'MYR');

        $this->sendMessageSafe(
            $chatId,
            "<b>📊 Your Stats</b>\n\n<b>Total Income:</b> {$currency} ".number_format($income, 2)."\n<b>Total Expense:</b> {$currency} ".number_format($expense, 2)."\n<b>Balance:</b> {$currency} ".number_format($income - $expense, 2)."\n\n<b>Transactions:</b> {$transactionCount}",
            true
        );
        $telegramTransaction->update(['status' => 'ignored']);
    }

    private function handleStatusCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessageSafe(
                $chatId,
                "❌ Your Telegram account is not linked.\n\nUse /link <code> to connect your account.\n\nGet your code from Settings → Telegram in the web app."
            );
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        // Get last transaction
        $lastTransaction = Transaction::where('user_id', $user->id)
            ->latest()
            ->first();

        $currency = config('hisabi.currency', 'MYR');

        $response = "<b>👤 Account Status</b>\n\n";
        $response .= "<b>Name:</b> {$user->name}\n";
        $response .= "<b>Email:</b> {$user->email}\n";
        $response .= "<b>Linked Since:</b> {$user->telegram_verified_at->format('Y-m-d H:i')}\n\n";

        if ($lastTransaction) {
            $amount = number_format($lastTransaction->amount, 2);
            $response .= "<b>Last Transaction:</b>\n";
            $response .= "{$currency} {$amount} - {$lastTransaction->description}\n";
            $response .= "({$lastTransaction->created_at->diffForHumans()})\n\n";
        } else {
            $response .= "<b>Last Transaction:</b> None yet\n\n";
        }

        $response .= "<i>Use /stats for detailed statistics</i>";

        $this->sendMessageSafe($chatId, $response, true);
        $telegramTransaction->update(['status' => 'ignored']);
    }

    private function handleLogoutCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessageSafe($chatId, "Your Telegram account is not linked to any Hisabi account.");
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }

        // Unlink the account
        $user->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
            'telegram_verification_code' => null,
        ]);

        $telegramTransaction->update(['status' => 'processed']);

        $this->sendMessageSafe(
            $chatId,
            "✅ <b>Account Unlinked</b>\n\nYour Telegram account has been successfully unlinked from {$user->name}.\n\nYou can link again anytime with /link <code>",
            true
        );
    }

    private function handleOcrStatusCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        try {
            $manager = new \App\Services\Ocr\OcrManager();

            if ($manager->isAvailable()) {
                $engines = array_map(fn ($e) => $e->getName(), $manager->getEngines());
                $engineList = implode(', ', $engines);

                $this->sendMessageSafe(
                    $chatId,
                    "✅ <b>OCR Service Ready</b>\n\nAvailable engines: {$engineList}\n\nSend me a receipt photo and I'll extract the text!",
                    true
                );
            } else {
                $this->sendMessageSafe(
                    $chatId,
                    "⚠️ <b>OCR Service Unavailable</b>\n\nNo OCR engines are currently available. Please try again later or type your transactions manually.",
                    true
                );
            }
        } catch (\Exception $e) {
            $this->sendMessageSafe(
                $chatId,
                "⚠️ <b>OCR Service Error</b>\n\nCould not check OCR service status. Please try again later.",
                true
            );
        }
        $telegramTransaction->update(['status' => 'ignored']);
    }

    private function handleUnknownCommand(string $chatId, TelegramTransaction $telegramTransaction): void
    {
        $this->sendMessageSafe($chatId, "Unknown command. Use /help for available commands.");
        $telegramTransaction->update(['status' => 'ignored']);
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        // Handle inline keyboard button clicks
        $chatId = $callbackQuery['message']['chat']['id'];

        // Acknowledge the callback
        try {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQuery['id'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to answer callback query', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Safely send a message to Telegram, catching any exceptions.
     */
    private function sendMessageSafe(string $chatId, string $text, bool $useHtml = false): void
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'text' => $text,
            ];

            if ($useHtml) {
                $params['parse_mode'] = 'HTML';
            }

            Telegram::sendMessage($params);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
