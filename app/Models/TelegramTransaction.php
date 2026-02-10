<?php

namespace App\Models;

use App\Enums\TelegramMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'telegram_message_id',
        'raw_message',
        'status',
        'transaction_id',
        'parsed_data',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'parsed_data' => 'json',
        'processed_at' => 'datetime',
        'status' => TelegramMessageStatus::class,
    ];

    /**
     * Get the user associated with this Telegram transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction created from this Telegram message.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('status', TelegramMessageStatus::PENDING);
    }

    /**
     * Mark as processed with transaction ID.
     */
    public function markAsProcessed(int $transactionId): void
    {
        $this->update([
            'status' => TelegramMessageStatus::PROCESSED,
            'transaction_id' => $transactionId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed with error message.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => TelegramMessageStatus::FAILED,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }
}
