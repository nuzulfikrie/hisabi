<?php

namespace App\Http\Controllers\Telegram;

use App\Actions\Telegram\CreateTransactionFromMessage;
use App\Actions\Telegram\DownloadTelegramFile;
use App\Actions\Telegram\ParseTransactionMessage;
use App\Actions\Telegram\ProcessReceiptImage;
use App\Actions\Telegram\SendTransactionConfirmation;
use App\Http\Controllers\Controller;
use App\Models\TelegramTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

            return response()->json(['status' => 'error'], 500);
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
            $this->handleCommand($text, $chatId, $username);
            $telegramTransaction->update(['status' => 'ignored']);

            return;
        }

        // Find linked user
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Please link your account first. Use /link command to get started.",
            ]);
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

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Could not process your message. Please use format:\n\nexpense 50 lunch\nor\nincome 1000 salary",
            ]);
        }
    }

    /**
     * Handle receipt photo with OCR
     */
    private function handleReceiptPhoto(array $message, string $chatId, string $messageId, ?string $username): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Please link your account first. Use /link command to get started.",
            ]);
            return;
        }

        // Send "processing" message
        $processingMsg = Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "🔄 Processing receipt...",
        ]);

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
            Telegram::deleteMessage([
                'chat_id' => $chatId,
                'message_id' => $processingMsg->get('message_id'),
            ]);

            // Send result
            $response = "<b>🧾 Receipt Processed</b>\n\n";
            $response .= "<b>Merchant:</b> {$merchant}\n";
            $response .= "<b>Amount:</b> {$currency} {$amount}\n";
            $response .= "<b>Date:</b> {$date}\n";
            $response .= "<b>OCR Engine:</b> {$engine}\n\n";
            $response .= "<i>To save this as a transaction, reply with:</i>\n";
            $response .= "<code>expense {$amount} {$merchant}</code>";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
                'parse_mode' => 'HTML',
            ]);

        } catch (\Exception $e) {
            Log::error('Receipt processing failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            // Delete processing message
            try {
                Telegram::deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $processingMsg->get('message_id'),
                ]);
            } catch (\Exception $deleteError) {
                // Ignore delete errors
            }

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Sorry, I couldn't process that receipt.\n\nError: {$e->getMessage()}\n\nPlease try:\n• Sending a clearer photo\n• Or type the details manually",
            ]);
        } finally {
            // Cleanup temp file
            if ($localFilePath) {
                DownloadTelegramFile::cleanup($localFilePath);
            }
        }
    }

    private function handleCommand(string $text, string $chatId, ?string $username): void
    {
        $parts = explode(' ', $text);
        $command = $parts[0];

        match ($command) {
            '/start' => $this->handleStartCommand($chatId),
            '/help' => $this->handleHelpCommand($chatId),
            '/link' => $this->handleLinkCommand($chatId, $username),
            '/stats' => $this->handleStatsCommand($chatId),
            '/ocr' => $this->handleOcrStatusCommand($chatId),
            default => $this->handleUnknownCommand($chatId),
        };
    }

    private function handleStartCommand(string $chatId): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "Welcome to Hisabi Bot! 🤖\n\nI can help you track expenses and income.\n\n<b>Text Input:</b>\n• expense 50 lunch\n• income 1000 salary\n• -25 coffee\n• +500 freelance\n\n<b>Receipt Photos:</b>\nJust send a photo of your receipt and I'll extract the details!\n\nGet started with /link",
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleHelpCommand(string $chatId): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "<b>Hisabi Bot Commands</b>\n\n/link - Link your account\n/stats - View your stats\n/ocr - Check OCR service status\n/help - Show this help\n\n<b>Text Formats:</b>\n• expense [amount] [description]\n• income [amount] [description]\n• -[amount] [description]\n• +[amount] [description]\n\n<b>Receipt Scanning:</b>\nSimply send a photo of any receipt!",
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleLinkCommand(string $chatId, ?string $username): void
    {
        // Generate verification code
        $code = strtoupper(substr(md5(uniqid()), 0, 8));

        // Store in cache temporarily
        $cacheKey = "telegram_link:{$code}";
        cache()->put($cacheKey, [
            'chat_id' => $chatId,
            'username' => $username,
        ], now()->addMinutes(30));

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "To link your account:\n\n1. Login to Hisabi web app\n2. Go to Settings → Telegram\n3. Enter this code: <b>{$code}</b>\n\nCode expires in 30 minutes.",
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleStatsCommand(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Please link your account first with /link",
            ]);

            return;
        }

        // Simple stats calculation
        $transactions = \App\Domains\Transaction\Models\Transaction::all();
        $income = $transactions->filter(fn ($t) => $t->brand?->category?->type === 'INCOME')->sum('amount');
        $expense = $transactions->filter(fn ($t) => $t->brand?->category?->type === 'EXPENSES')->sum('amount');

        $currency = config('hisabi.currency', 'MYR');

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "<b>Your Stats</b>\n\nTotal Income: {$currency} {$income}\nTotal Expense: {$currency} {$expense}\nBalance: {$currency} ".($income - $expense),
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleOcrStatusCommand(string $chatId): void
    {
        $manager = new \App\Services\Ocr\OcrManager();
        
        if ($manager->isAvailable()) {
            $engines = array_map(fn ($e) => $e->getName(), $manager->getEngines());
            $engineList = implode(', ', $engines);
            
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "✅ <b>OCR Service Ready</b>\n\nAvailable engines: {$engineList}\n\nSend me a receipt photo and I'll extract the text!",
                'parse_mode' => 'HTML',
            ]);
        } else {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "⚠️ <b>OCR Service Unavailable</b>\n\nNo OCR engines are currently available. Please try again later or type your transactions manually.",
                'parse_mode' => 'HTML',
            ]);
        }
    }

    private function handleUnknownCommand(string $chatId): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "Unknown command. Use /help for available commands.",
        ]);
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        // Handle inline keyboard button clicks
        $chatId = $callbackQuery['message']['chat']['id'];

        // Acknowledge the callback
        Telegram::answerCallbackQuery([
            'callback_query_id' => $callbackQuery['id'],
        ]);
    }
}
