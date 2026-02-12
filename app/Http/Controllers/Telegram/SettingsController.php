<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\LinkTelegramRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display Telegram settings.
     */
    public function index(): Response
    {
        $user = auth()->user();

        // Get recent telegram transactions (last 10)
        $recentTransactions = $user->telegramTransactions()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'raw_message' => $transaction->raw_message,
                'status' => $transaction->status->value,
                'status_label' => $transaction->status->label(),
                'status_badge' => $transaction->status->badge(),
                'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
                'processed_at' => $transaction->processed_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('Settings/Telegram', [
            'isLinked' => $user->hasTelegramLinked(),
            'telegramUsername' => $user->telegram_username,
            'telegramChatId' => $user->telegram_chat_id,
            'telegramVerifiedAt' => $user->telegram_verified_at?->format('Y-m-d H:i:s'),
            'verificationCode' => $user->telegram_verification_code,
            'recentTransactions' => $recentTransactions,
            'rateLimit' => [
                'attempts' => RateLimiter::attempts($this->getRateLimitKey($user)),
                'available_in' => RateLimiter::availableIn($this->getRateLimitKey($user)),
            ],
        ]);
    }

    /**
     * Link Telegram account with verification code.
     */
    public function link(LinkTelegramRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $code = $validated['code'];

        $user = auth()->user();

        // Check cache for Telegram data
        $cacheKey = "telegram_link:{$code}";
        $cachedData = Cache::get($cacheKey);

        if (! $cachedData) {
            return redirect()->back()->with('error', 'Invalid or expired verification code');
        }

        // Check if code was generated for a different user
        if (isset($cachedData['user_id']) && $cachedData['user_id'] !== $user->id) {
            return redirect()->back()->with('error', 'This code belongs to a different user');
        }

        // Update user
        $user->update([
            'telegram_chat_id' => $cachedData['chat_id'],
            'telegram_username' => $cachedData['username'],
            'telegram_verified_at' => now(),
            'telegram_verification_code' => null,
        ]);

        // Clear cache and any other codes for this user
        Cache::forget($cacheKey);
        $this->clearAllUserCodes($user);

        return redirect()->back()->with('success', 'Telegram account linked successfully');
    }

    /**
     * Generate a new verification code.
     */
    public function generateCode(): RedirectResponse
    {
        $user = auth()->user();
        $rateLimitKey = $this->getRateLimitKey($user);

        // Check rate limit (max 3 attempts per 10 minutes)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);

            return redirect()->back()->with('error', "Too many attempts. Please try again in {$minutes} minutes.");
        }

        // Record attempt
        RateLimiter::hit($rateLimitKey, 600); // 10 minutes decay

        // Generate 6-digit numeric OTP
        $code = $this->generateNumericOtp();

        // Clear any existing codes for this user first
        $this->clearAllUserCodes($user);

        // Store in cache with 10 minute TTL
        $cacheKey = "telegram_link:{$code}";
        Cache::put($cacheKey, [
            'user_id' => $user->id,
            'chat_id' => null, // Will be filled when user sends /link command
            'username' => null,
            'generated_at' => now()->toIso8601String(),
        ], now()->addMinutes(10));

        // Also store in user record for reference
        $user->update(['telegram_verification_code' => $code]);

        return redirect()->back()->with([
            'success' => 'Verification code generated. Code expires in 10 minutes.',
            'verification_code' => $code,
        ]);
    }

    /**
     * Unlink Telegram account.
     */
    public function unlink(): RedirectResponse
    {
        $user = auth()->user();

        // Clear any existing codes
        $this->clearAllUserCodes($user);

        $user->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
            'telegram_verification_code' => null,
        ]);

        return redirect()->back()->with('success', 'Telegram account unlinked successfully');
    }

    /**
     * Generate a 6-digit numeric OTP.
     */
    private function generateNumericOtp(): string
    {
        // Generate random 6-digit number, ensuring it doesn't start with 0
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the rate limit key for a user.
     */
    private function getRateLimitKey(User $user): string
    {
        return "telegram_otp:{$user->id}";
    }

    /**
     * Clear all verification codes for a user.
     */
    private function clearAllUserCodes(User $user): void
    {
        // Clear the code stored in the user record from cache if exists
        if ($user->telegram_verification_code) {
            Cache::forget("telegram_link:{$user->telegram_verification_code}");
        }
    }
}
