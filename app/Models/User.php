<?php

namespace App\Models;

use App\Concerns\HasActiveStatus;
use App\Concerns\HasSettings;
use App\Concerns\InteractsWithRole;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasActiveStatus, HasApiTokens, HasFactory, HasSettings, InteractsWithRole, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
        'locale',
        'timezone',
        'phone',
        'last_login_at',
        'telegram_chat_id',
        'telegram_username',
        'telegram_verified_at',
        'telegram_verification_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telegram_verification_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'telegram_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'status' => UserStatus::class,
        'role' => UserRole::class,
    ];

    /**
     * Get telegram transactions for this user.
     */
    public function telegramTransactions(): HasMany
    {
        return $this->hasMany(TelegramTransaction::class);
    }

    /**
     * Check if user has linked Telegram account.
     */
    public function hasTelegramLinked(): bool
    {
        return ! empty($this->telegram_chat_id) && ! empty($this->telegram_verified_at);
    }

    /**
     * Generate a verification code for Telegram linking.
     * @deprecated Use SettingsController::generateCode() instead for proper rate limiting and caching.
     */
    public function generateTelegramVerificationCode(): string
    {
        // Generate 6-digit numeric OTP
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $this->update(['telegram_verification_code' => $code]);

        return $code;
    }
}
