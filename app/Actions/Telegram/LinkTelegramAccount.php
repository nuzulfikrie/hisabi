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

    public function handle(string $chatId, string $username, string $verificationCode): ?User
    {
        // Find user by verification code
        $cacheKey = "telegram_link:{$verificationCode}";
        $cachedData = Cache::get($cacheKey);

        if (! $cachedData) {
            // Try to find user with matching verification code directly
            $user = User::where('telegram_verification_code', $verificationCode)->first();
        } else {
            $user = User::find($cachedData['user_id'] ?? null);
        }

        if (! $user) {
            Log::warning('Invalid Telegram verification code', [
                'chat_id' => $chatId,
                'username' => $username,
            ]);

            return null;
        }

        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_verified_at' => now(),
            'telegram_verification_code' => null,
        ]);

        // Clear cache
        Cache::forget($cacheKey);

        Log::info('Telegram account linked', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
        ]);

        return $user;
    }
}
