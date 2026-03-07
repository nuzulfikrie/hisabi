<?php

namespace Tests\Feature\Api\V1;

use App\Domains\ApiKey\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_it_requires_authentication_for_index(): void
    {
        $response = $this->getJson('/api/v1/api-keys');
        $response->assertStatus(401);
    }

    public function test_it_returns_empty_array_when_no_api_keys(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'apiKeys');
    }

    public function test_it_returns_user_api_keys(): void
    {
        ApiKey::create([
            'user_id' => $this->user->id,
            'name' => 'Test Key',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'apiKeys' => [
                    '*' => [
                        'uuid',
                        'name',
                        'created_at',
                        'last_used_at',
                    ]
                ]
            ])
            ->assertJsonCount(1, 'apiKeys')
            ->assertJsonPath('apiKeys.0.name', 'Test Key');
    }

    public function test_it_does_not_return_other_users_api_keys(): void
    {
        $otherUser = User::factory()->create();

        ApiKey::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Key',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/api-keys');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'apiKeys');
    }

    public function test_it_requires_authentication_for_store(): void
    {
        $response = $this->postJson('/api/v1/api-keys', ['name' => 'Test']);
        $response->assertStatus(401);
    }

    public function test_it_creates_api_key(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', [
                'name' => 'Mobile App',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'apiKey' => [
                    'uuid',
                    'name',
                    'key',
                    'created_at',
                    'last_used_at',
                ]
            ])
            ->assertJsonPath('apiKey.name', 'Mobile App');

        $this->assertStringStartsWith('his_', $response->json('apiKey.key'));
        $this->assertDatabaseHas('api_keys', [
            'user_id' => $this->user->id,
            'name' => 'Mobile App',
        ]);
    }

    public function test_it_validates_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/api-keys', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_requires_authentication_for_destroy(): void
    {
        $apiKey = ApiKey::create([
            'user_id' => $this->user->id,
            'name' => 'Test Key',
        ]);

        $response = $this->deleteJson("/api/v1/api-keys/{$apiKey->uuid}");
        $response->assertStatus(401);
    }

    public function test_it_deletes_api_key(): void
    {
        $apiKey = ApiKey::create([
            'user_id' => $this->user->id,
            'name' => 'Test Key',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$apiKey->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('apiKey.uuid', $apiKey->uuid)
            ->assertJsonPath('apiKey.name', 'Test Key');

        $this->assertDatabaseMissing('api_keys', [
            'uuid' => $apiKey->uuid,
        ]);
    }

    public function test_it_cannot_delete_other_users_api_key(): void
    {
        $otherUser = User::factory()->create();
        $apiKey = ApiKey::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Key',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/api-keys/{$apiKey->uuid}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('api_keys', [
            'uuid' => $apiKey->uuid,
        ]);
    }

    public function test_it_returns_404_for_non_existent_api_key(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/api-keys/non-existent-uuid');

        $response->assertStatus(404);
    }
}
