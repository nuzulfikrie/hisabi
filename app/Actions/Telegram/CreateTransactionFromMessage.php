<?php

namespace App\Actions\Telegram;

use App\Domains\Brand\Models\Brand;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\TelegramTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Create a transaction from parsed Telegram message data.
 */
class CreateTransactionFromMessage
{
    use AsAction;

    /**
     * Handle the action.
     */
    public function handle(TelegramTransaction $telegramTransaction, array $parsedData): Transaction
    {
        return DB::transaction(function () use ($telegramTransaction, $parsedData) {
            // Find user by telegram chat ID
            $user = User::where('telegram_chat_id', $telegramTransaction->telegram_chat_id)->first();

            if (! $user) {
                throw new \Exception('User not linked to this Telegram account');
            }

            // Find or create category based on type
            $category = Category::firstOrCreate(
                ['type' => $parsedData['category_type']],
                ['name' => $parsedData['category_type']]
            );

            // Find or create brand
            $brand = Brand::findOrCreateNew($parsedData['brand_name']);
            
            // Ensure brand has a category
            if (! $brand->category_id) {
                $brand->update(['category_id' => $category->id]);
            }

            // Create the transaction
            $transaction = Transaction::create([
                'amount' => $parsedData['amount'],
                'brand_id' => $brand->id,
            ]);

            // Update telegram transaction record
            $telegramTransaction->markAsProcessed($transaction->id);

            Log::info('Transaction created from Telegram', [
                'telegram_transaction_id' => $telegramTransaction->id,
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'brand_id' => $brand->id,
                'amount' => $parsedData['amount'],
            ]);

            return $transaction;
        });
    }

    /**
     * Handle failed transaction creation.
     */
    public function failed(TelegramTransaction $telegramTransaction, \Throwable $exception): void
    {
        $telegramTransaction->markAsFailed($exception->getMessage());

        Log::error('Failed to create transaction from Telegram', [
            'telegram_transaction_id' => $telegramTransaction->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
