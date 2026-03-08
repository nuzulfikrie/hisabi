<?php

namespace App\Actions\Telegram;

use App\Domains\Transaction\Models\Transaction;
use Telegram\Bot\Laravel\Facades\Telegram;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Send transaction confirmation message to Telegram.
 */
class SendTransactionConfirmation
{
    use AsAction;

    public function handle(Transaction $transaction): void
    {
        // Get user through brand -> category (not directly linked to user yet)
        // For now, we don't have user-transaction link, so we'll skip confirmation
        // or we can find the user from TelegramTransaction
        $telegramTransaction = \App\Models\TelegramTransaction::where('transaction_id', $transaction->id)->first();
        
        if (! $telegramTransaction) {
            return;
        }

        $chatId = $telegramTransaction->telegram_chat_id;

        if (! $chatId) {
            return;
        }

        $message = $this->formatMessage($transaction);

        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }

    private function formatMessage(Transaction $transaction): string
    {
        $brand = $transaction->brand?->name ?? 'Unknown';
        $amount = number_format($transaction->amount, 2);
        $currency = config('hisabi.currency', 'MYR');
        $date = $transaction->created_at->format('Y-m-d H:i');
        $category = $transaction->brand?->category?->type ?? 'EXPENSES';

        return <<<MESSAGE
<b>✅ Transaction Recorded</b>

<b>Brand:</b> {$brand}
<b>Amount:</b> {$currency} {$amount}
<b>Category:</b> {$category}
<b>Date:</b> {$date}

View in app: /transactions
MESSAGE;
    }
}
