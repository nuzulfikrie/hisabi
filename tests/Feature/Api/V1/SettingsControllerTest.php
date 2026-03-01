<?php

namespace Tests\Feature\Api\V1;

use App\Domains\User\Models\UserPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_it_requires_authentication_for_get_preferences(): void
    {
        $response = $this->getJson('/api/v1/user/preferences');
        $response->assertStatus(401);
    }

    public function test_it_creates_default_preferences_on_first_get(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'preferences' => [
                    'uuid',
                    'currency',
                    'date_format',
                    'theme',
                    'language',
                    'default_transaction_type',
                    'email_notifications',
                    'push_notifications',
                ]
            ])
            ->assertJsonPath('preferences.currency', 'USD')
            ->assertJsonPath('preferences.date_format', 'DD/MM/YYYY')
            ->assertJsonPath('preferences.theme', 'system')
            ->assertJsonPath('preferences.language', 'en')
            ->assertJsonPath('preferences.default_transaction_type', 'expense')
            ->assertJsonPath('preferences.email_notifications', true)
            ->assertJsonPath('preferences.push_notifications', true);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'currency' => 'USD',
        ]);
    }

    public function test_it_returns_existing_preferences(): void
    {
        UserPreference::create([
            'user_id' => $this->user->id,
            'currency' => 'MYR',
            'theme' => 'dark',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('preferences.currency', 'MYR')
            ->assertJsonPath('preferences.theme', 'dark');
    }

    public function test_it_requires_authentication_for_update_preferences(): void
    {
        $response = $this->putJson('/api/v1/user/preferences', []);
        $response->assertStatus(401);
    }

    public function test_it_updates_preferences(): void
    {
        UserPreference::create([
            'user_id' => $this->user->id,
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/preferences', [
                'currency' => 'SGD',
                'theme' => 'light',
                'email_notifications' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('preferences.currency', 'SGD')
            ->assertJsonPath('preferences.theme', 'light')
            ->assertJsonPath('preferences.email_notifications', false);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'currency' => 'SGD',
            'theme' => 'light',
        ]);
    }

    public function test_it_validates_currency(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/preferences', [
                'currency' => 'INVALID',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    }

    public function test_it_validates_theme(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/preferences', [
                'theme' => 'invalid_theme',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['theme']);
    }

    public function test_it_validates_date_format(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/preferences', [
                'date_format' => 'INVALID',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_format']);
    }

    public function test_it_validates_language(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/preferences', [
                'language' => 'xx',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['language']);
    }

    public function test_it_validates_default_transaction_type(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/user/preferences', [
                'default_transaction_type' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_transaction_type']);
    }

    public function test_preferences_are_isolated_per_user(): void
    {
        $otherUser = User::factory()->create();

        UserPreference::create([
            'user_id' => $this->user->id,
            'currency' => 'USD',
        ]);

        UserPreference::create([
            'user_id' => $otherUser->id,
            'currency' => 'MYR',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/user/preferences');

        $response->assertJsonPath('preferences.currency', 'USD');
    }
}
