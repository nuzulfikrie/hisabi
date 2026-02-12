<?php

namespace Tests\Feature\Telegram;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TelegramSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_displays_telegram_settings_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/telegram');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Telegram')
            ->has('isLinked')
            ->has('recentTransactions')
        );
    }

    public function test_requires_authentication()
    {
        $response = $this->get('/settings/telegram');

        $response->assertRedirect('/login');
    }

    public function test_shows_linked_status_when_telegram_is_linked()
    {
        $user = User::factory()->withTelegram()->create();

        $response = $this->actingAs($user)->get('/settings/telegram');

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Telegram')
            ->where('isLinked', true)
            ->where('telegramUsername', $user->telegram_username)
            ->where('telegramChatId', $user->telegram_chat_id)
        );
    }

    public function test_shows_unlinked_status_when_telegram_is_not_linked()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/settings/telegram');

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Telegram')
            ->where('isLinked', false)
            ->where('telegramUsername', null)
        );
    }

    public function test_generates_verification_code()
    {
        $user = User::factory()->create();
        RateLimiter::clear("telegram_otp:{$user->id}");

        $response = $this->actingAs($user)->post('/settings/telegram/generate-code');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('verification_code');

        $code = session('verification_code');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        // Verify code is stored in cache
        $this->assertNotNull(Cache::get("telegram_link:{$code}"));
    }

    public function test_rate_limits_code_generation()
    {
        $user = User::factory()->create();
        RateLimiter::clear("telegram_otp:{$user->id}");

        // Generate 3 codes (the limit)
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->post('/settings/telegram/generate-code');
        }

        // 4th attempt should be rate limited
        $response = $this->actingAs($user)->post('/settings/telegram/generate-code');

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($error) => str_contains($error, 'Too many attempts'));
    }

    public function test_links_telegram_account_with_valid_code()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
        ]);

        $code = '123456';
        Cache::put("telegram_link:{$code}", [
            'user_id' => $user->id,
            'chat_id' => '123456789',
            'username' => 'testuser',
        ], now()->addMinutes(10));

        $response = $this->actingAs($user)->post('/settings/telegram/link', [
            'code' => $code,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Telegram account linked successfully');

        $user->refresh();
        $this->assertNotNull($user->telegram_chat_id);
        $this->assertEquals('123456789', $user->telegram_chat_id);
        $this->assertEquals('testuser', $user->telegram_username);
    }

    public function test_rejects_invalid_verification_code()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/telegram/link', [
            'code' => '000000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Invalid or expired verification code');
    }

    public function test_rejects_non_numeric_code()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/telegram/link', [
            'code' => 'ABC123',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_rejects_code_wrong_length()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/settings/telegram/link', [
            'code' => '12345',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_unlinks_telegram_account()
    {
        $user = User::factory()->withTelegram()->create();

        $response = $this->actingAs($user)->post('/settings/telegram/unlink');

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Telegram account unlinked successfully');

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertNull($user->telegram_username);
        $this->assertNull($user->telegram_verified_at);
    }
}
