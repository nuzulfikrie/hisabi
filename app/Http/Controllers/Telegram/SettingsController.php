<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display Telegram settings.
     */
    public function index(): View
    {
        $user = auth()->user();

        return view('telegram.settings', [
            'user' => $user,
            'isLinked' => $user->hasTelegramLinked(),
            'verificationCode' => $user->telegram_verification_code,
        ]);
    }

    /**
     * Link Telegram account with verification code.
     */
    public function link(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'verification_code' => 'required|string|size:8',
        ]);

        $user = auth()->user();
        $code = strtoupper($validated['verification_code']);

        // Check cache for Telegram data
        $cacheKey = "telegram_link:{$code}";
        $cachedData = Cache::get($cacheKey);

        if (! $cachedData) {
            return redirect()->back()->with('error', 'Invalid or expired verification code');
        }

        // Update user
        $user->update([
            'telegram_chat_id' => $cachedData['chat_id'],
            'telegram_username' => $cachedData['username'],
            'telegram_verified_at' => now(),
            'telegram_verification_code' => null,
        ]);

        // Clear cache
        Cache::forget($cacheKey);

        return redirect()->back()->with('success', 'Telegram account linked successfully');
    }

    /**
     * Generate a new verification code.
     */
    public function generateCode(): RedirectResponse
    {
        $user = auth()->user();
        $code = $user->generateTelegramVerificationCode();

        return redirect()->back()->with([
            'success' => 'Verification code generated',
            'verification_code' => $code,
        ]);
    }

    /**
     * Unlink Telegram account.
     */
    public function unlink(): RedirectResponse
    {
        $user = auth()->user();

        $user->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
            'telegram_verification_code' => null,
        ]);

        return redirect()->back()->with('success', 'Telegram account unlinked successfully');
    }
}
