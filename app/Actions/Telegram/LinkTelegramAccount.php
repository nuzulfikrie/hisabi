<?php

namespace App\Actions\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Link Telegram account to user.
 */
class LinkTelegramAccount
{
    use AsAction;

    public function handle(string $chatId, ?string $username, string $verificationCode): ?User
    {
        // Normalize code (ensure it's 6 digits)
        $code = trim($verificationCode);

        // Validate code format
        if (! preg_match('/^\d{6}$/', $code)) {
            Log::warning('Invalid Telegram verification code format', [
                'chat_id' => $chatId,
                'username' => $username,
                'code' => $code,
            ]);

            return null;
        }

        // Find user by verification code from cache
        $cacheKey = "telegram_link:{$code}";
        $cachedData = Cache::get($cacheKey);

        if (! $cachedData) {
            Log::warning('Expired or invalid Telegram verification code', [
                'chat_id' => $chatId,
                'username' => $username,
            ]);

            return null;
        }

        $user = User::find($cachedData['user_id'] ?? null);

        if (! $user) {
            Log::warning('User not found for Telegram verification code', [
                'chat_id' => $chatId,
                'username' => $username,
                'code' => $code,
            ]);

            return null;
        }

        // Clear ALL old codes for this user before linking
        $this->clearAllUserCodes($user);

        // Update user with Telegram details
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_verified_at' => now(),
            'telegram_verification_code' => null,
        ]);

        // Clear the used code from cache
        Cache::forget($cacheKey);

        Log::info('Telegram account linked', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'username' => $username,
        ]);

        return $user;
    }

    /**
     * Clear all verification codes for a user.
     */
    private function clearAllUserCodes(User $user): void
    {
        // Clear the current code from cache if it exists
        if ($user->telegram_verification_code) {
            Cache::forget("telegram_link:{$user->telegram_verification_code}");
        }

        // Also clear any other potential codes for this user by pattern
        // Note: In production with Redis, you might want to use a Set to track user codes
        // For now, we clear the known code
    }
}
