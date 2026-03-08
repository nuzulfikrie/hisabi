<?php

namespace Tests\Feature\Telegram;

use App\Models\User;
use App\Models\TelegramTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function mockTelegramMessage(array $overrides = []): array
    {
        return array_merge([
            'update_id' => random_int(100000, 999999),
            'message' => [
                'message_id' => random_int(1, 1000),
                'from' => [
                    'id' => random_int(100000, 999999),
                    'is_bot' => false,
                    'first_name' => 'Test',
                    'username' => 'testuser',
                ],
                'chat' => [
                    'id' => '123456789',
                    'first_name' => 'Test',
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ], $overrides);
    }

    public function test_handles_start_command()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/start',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        // Verify transaction was recorded
        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/start',
            'status' => 'ignored',
        ]);
    }

    public function test_handles_help_command()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/help',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/help',
            'status' => 'ignored',
        ]);
    }

    public function test_handles_link_command_with_valid_code()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
        ]);

        $code = '123456';
        Cache::put("telegram_link:{$code}", [
            'user_id' => $user->id,
            'chat_id' => null,
            'username' => null,
        ], now()->addMinutes(10));

        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test', 'username' => 'testuser'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/link 123456',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals('123456789', $user->telegram_chat_id);
        $this->assertEquals('testuser', $user->telegram_username);
    }

    public function test_rejects_link_command_with_invalid_code()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/link 000000',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        // Verify transaction was recorded as ignored
        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/link 000000',
            'status' => 'ignored',
        ]);
    }

    public function test_rejects_link_command_with_non_numeric_code()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/link ABC123',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/link ABC123',
            'status' => 'ignored',
        ]);
    }

    public function test_handles_link_command_without_code()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/link',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/link',
            'status' => 'ignored',
        ]);
    }

    public function test_handles_stats_command_for_linked_user()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_verified_at' => now(),
        ]);

        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/stats',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/stats',
            'status' => 'ignored',
        ]);
    }

    public function test_stats_command_requires_linked_account()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/stats',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/stats',
            'status' => 'ignored',
        ]);
    }

    public function test_handles_status_command()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_verified_at' => now(),
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/status',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/status',
            'status' => 'ignored',
        ]);
    }

    public function test_status_command_shows_unlinked_message()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/status',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/status',
            'status' => 'ignored',
        ]);
    }

    public function test_handles_logout_command()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_username' => 'testuser',
            'telegram_verified_at' => now(),
        ]);

        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/logout',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertNull($user->telegram_username);

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/logout',
            'status' => 'processed',
        ]);
    }

    public function test_handles_unknown_command_gracefully()
    {
        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => '/unknowncommand',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => '/unknowncommand',
            'status' => 'ignored',
        ]);
    }

    public function test_parses_transaction_message()
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_verified_at' => now(),
        ]);

        $data = $this->mockTelegramMessage([
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 123, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => '123456789', 'type' => 'private'],
                'date' => time(),
                'text' => 'expense 50 lunch',
            ],
        ]);

        $response = $this->postJson('/telegram/webhook', $data);

        $response->assertOk();

        $this->assertDatabaseHas('telegram_transactions', [
            'telegram_chat_id' => '123456789',
            'raw_message' => 'expense 50 lunch',
            'user_id' => $user->id,
        ]);
    }
}
