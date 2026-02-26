<?php

namespace Tests\Feature\Api\V1;

use App\Domains\SmsParser\Models\SmsParserRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsParserControllerTest extends TestCase
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
        $response = $this->getJson('/api/v1/sms-parser-rules');
        $response->assertStatus(401);
    }

    public function test_it_returns_empty_array_when_no_rules(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sms-parser-rules');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'rules');
    }

    public function test_it_returns_user_rules(): void
    {
        SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'Maybank Debit',
            'bank_name' => 'Maybank',
            'pattern' => '/RM(?P<amount>[\d,.]+).*?debited/i',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sms-parser-rules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'rules' => [
                    '*' => [
                        'uuid',
                        'name',
                        'bank_name',
                        'pattern',
                        'is_active',
                        'created_at',
                    ]
                ]
            ])
            ->assertJsonCount(1, 'rules')
            ->assertJsonPath('rules.0.name', 'Maybank Debit');
    }

    public function test_it_does_not_return_other_users_rules(): void
    {
        $otherUser = User::factory()->create();

        SmsParserRule::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Rule',
            'bank_name' => 'Other Bank',
            'pattern' => '/test/i',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sms-parser-rules');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'rules');
    }

    public function test_it_requires_authentication_for_store(): void
    {
        $response = $this->postJson('/api/v1/sms-parser-rules', []);
        $response->assertStatus(401);
    }

    public function test_it_creates_parser_rule(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sms-parser-rules', [
                'name' => 'CIMB Transfer',
                'bank_name' => 'CIMB',
                'pattern' => '/RM(?P<amount>[\d,.]+).*?transferred/i',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'rule' => [
                    'uuid',
                    'name',
                    'bank_name',
                    'pattern',
                    'is_active',
                    'created_at',
                ]
            ])
            ->assertJsonPath('rule.name', 'CIMB Transfer')
            ->assertJsonPath('rule.bank_name', 'CIMB');

        $this->assertDatabaseHas('sms_parser_rules', [
            'user_id' => $this->user->id,
            'name' => 'CIMB Transfer',
            'bank_name' => 'CIMB',
        ]);
    }

    public function test_it_validates_required_fields_for_store(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sms-parser-rules', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'bank_name', 'pattern']);
    }

    public function test_it_requires_authentication_for_update(): void
    {
        $rule = SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'Test Rule',
            'bank_name' => 'Test Bank',
            'pattern' => '/test/i',
        ]);

        $response = $this->putJson("/api/v1/sms-parser-rules/{$rule->uuid}", []);
        $response->assertStatus(401);
    }

    public function test_it_updates_parser_rule(): void
    {
        $rule = SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
            'bank_name' => 'Old Bank',
            'pattern' => '/old/i',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sms-parser-rules/{$rule->uuid}", [
                'name' => 'New Name',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('rule.name', 'New Name')
            ->assertJsonPath('rule.bank_name', 'Old Bank')
            ->assertJsonPath('rule.is_active', false);

        $this->assertDatabaseHas('sms_parser_rules', [
            'uuid' => $rule->uuid,
            'name' => 'New Name',
            'is_active' => false,
        ]);
    }

    public function test_it_cannot_update_other_users_rule(): void
    {
        $otherUser = User::factory()->create();
        $rule = SmsParserRule::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Rule',
            'bank_name' => 'Other Bank',
            'pattern' => '/other/i',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/sms-parser-rules/{$rule->uuid}", [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(404);
    }

    public function test_it_requires_authentication_for_destroy(): void
    {
        $rule = SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'Test Rule',
            'bank_name' => 'Test Bank',
            'pattern' => '/test/i',
        ]);

        $response = $this->deleteJson("/api/v1/sms-parser-rules/{$rule->uuid}");
        $response->assertStatus(401);
    }

    public function test_it_deletes_parser_rule(): void
    {
        $rule = SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'Test Rule',
            'bank_name' => 'Test Bank',
            'pattern' => '/test/i',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sms-parser-rules/{$rule->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('rule.uuid', $rule->uuid);

        $this->assertDatabaseMissing('sms_parser_rules', [
            'uuid' => $rule->uuid,
        ]);
    }

    public function test_it_cannot_delete_other_users_rule(): void
    {
        $otherUser = User::factory()->create();
        $rule = SmsParserRule::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Rule',
            'bank_name' => 'Other Bank',
            'pattern' => '/other/i',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/sms-parser-rules/{$rule->uuid}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('sms_parser_rules', [
            'uuid' => $rule->uuid,
        ]);
    }

    public function test_it_requires_authentication_for_test(): void
    {
        $response = $this->postJson('/api/v1/sms-parser-rules/test', [
            'sms' => 'Test SMS',
        ]);
        $response->assertStatus(401);
    }

    public function test_it_tests_parser_with_specific_pattern(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sms-parser-rules/test', [
                'sms' => 'RM100.50 debited from your account',
                'pattern' => '/RM(?P<amount>[\d,.]+)/i',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('matched', true)
            ->assertJsonPath('matches.amount', '100.50');
    }

    public function test_it_tests_parser_against_user_rules(): void
    {
        SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'Maybank',
            'bank_name' => 'Maybank',
            'pattern' => '/RM(?P<amount>[\d,.]+).*?from\s+(?P<description>.+?)(?:\s+on|$)/i',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sms-parser-rules/test', [
                'sms' => 'RM50.00 debited from your account to GROCERY STORE on 01/01/2024',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('matched', true)
            ->assertJsonPath('rule.name', 'Maybank')
            ->assertJsonPath('matches.amount', '50.00')
            ->assertJsonPath('matches.description', 'GROCERY STORE');
    }

    public function test_it_returns_no_match_when_pattern_fails(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sms-parser-rules/test', [
                'sms' => 'Some random text',
                'pattern' => '/RM(?P<amount>[\d,.]+)/i',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('matched', false)
            ->assertJsonPath('matches', []);
    }

    public function test_it_validates_sms_is_required_for_test(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/sms-parser-rules/test', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sms']);
    }

    public function test_rules_are_isolated_per_user(): void
    {
        $otherUser = User::factory()->create();

        SmsParserRule::create([
            'user_id' => $this->user->id,
            'name' => 'User Rule',
            'bank_name' => 'Bank A',
            'pattern' => '/patternA/i',
        ]);

        SmsParserRule::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Rule',
            'bank_name' => 'Bank B',
            'pattern' => '/patternB/i',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/sms-parser-rules');

        $response->assertJsonCount(1, 'rules')
            ->assertJsonPath('rules.0.name', 'User Rule');
    }
}
