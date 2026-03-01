<?php

use App\Domains\Audit\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

describe('AuditLogController', function () {
    describe('index', function () {
        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/admin/audit-logs');
            
            $response->assertUnauthorized();
        });

        it('requires admin access', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/admin/audit-logs');
            
            $response->assertForbidden()
                ->assertJson(['message' => 'Unauthorized']);
        });

        it('returns paginated audit logs for admin', function () {
            AuditLog::factory()->count(15)->create();
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs');
            
            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ]);
            
            expect($response->json('meta.total'))->toBe(15);
        });

        it('filters by action', function () {
            AuditLog::factory()->create(['action' => 'create']);
            AuditLog::factory()->create(['action' => 'delete']);
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs?action=create');
            
            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.action'))->toBe('create');
        });

        it('filters by entity type', function () {
            AuditLog::factory()->create(['entity_type' => 'Transaction']);
            AuditLog::factory()->create(['entity_type' => 'User']);
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs?entity_type=Transaction');
            
            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.entity_type'))->toBe('Transaction');
        });

        it('filters by date range', function () {
            AuditLog::factory()->create([
                'created_at' => now()->subDays(5),
            ]);
            AuditLog::factory()->create([
                'created_at' => now()->subDays(10),
            ]);
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs?date_from=' . now()->subDays(7)->format('Y-m-d') . &date_to=' . now()->format('Y-m-d'));
            
            $response->assertOk();
            expect($response->json('meta.total'))->toBe(1);
        });

        it('supports custom per_page', function () {
            AuditLog::factory()->count(25)->create();
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs?per_page=10');
            
            $response->assertOk();
            expect($response->json('meta.per_page'))->toBe(10);
            expect(count($response->json('data')))->toBe(10);
        });
    });

    describe('show', function () {
        it('returns audit log details with diff', function () {
            $log = AuditLog::factory()->create([
                'old_values' => ['name' => 'Old Name'],
                'new_values' => ['name' => 'New Name'],
            ]);
            
            $response = $this->actingAs($this->admin)
                ->getJson("/api/v1/admin/audit-logs/{$log->id}");
            
            $response->assertOk()
                ->assertJsonStructure([
                    'data',
                    'diff',
                ])
                ->assertJsonPath('data.id', $log->id)
                ->assertJsonPath('diff.name.old', 'Old Name')
                ->assertJsonPath('diff.name.new', 'New Name');
        });

        it('returns 404 for non-existent audit log', function () {
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs/non-existent-uuid');
            
            $response->assertNotFound();
        });
    });

    describe('actions', function () {
        it('returns list of unique actions', function () {
            AuditLog::factory()->create(['action' => 'create']);
            AuditLog::factory()->create(['action' => 'create']);
            AuditLog::factory()->create(['action' => 'update']);
            AuditLog::factory()->create(['action' => 'delete']);
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs/actions');
            
            $response->assertOk()
                ->assertJsonCount(3, 'data');
        });
    });

    describe('entityTypes', function () {
        it('returns list of unique entity types', function () {
            AuditLog::factory()->create(['entity_type' => 'Transaction']);
            AuditLog::factory()->create(['entity_type' => 'Transaction']);
            AuditLog::factory()->create(['entity_type' => 'User']);
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/audit-logs/entity-types');
            
            $response->assertOk()
                ->assertJsonCount(2, 'data');
        });
    });
});

describe('SystemHealthController', function () {
    describe('index', function () {
        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/admin/system-health');
            
            $response->assertUnauthorized();
        });

        it('requires admin access', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/admin/system-health');
            
            $response->assertForbidden()
                ->assertJson(['message' => 'Unauthorized']);
        });

        it('returns system health data for admin', function () {
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/system-health');
            
            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'database' => [
                            'status',
                            'message',
                            'driver',
                        ],
                        'storage' => [
                            'status',
                            'total',
                            'used',
                            'free',
                            'usage_percentage',
                        ],
                        'queue' => [
                            'status',
                            'connection',
                            'pending_jobs',
                            'failed_jobs',
                        ],
                        'errors' => [
                            'count',
                            'errors',
                        ],
                        'users' => [
                            'total',
                            'active_today',
                        ],
                        'transactions' => [
                            'total_count',
                            'today_count',
                            'total_amount',
                            'today_amount',
                        ],
                        'system' => [
                            'php_version',
                            'laravel_version',
                            'environment',
                            'debug_mode',
                            'timezone',
                            'cache_driver',
                            'session_driver',
                        ],
                    ],
                ]);
        });

        it('returns database connection status', function () {
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/system-health');
            
            $response->assertOk();
            
            $database = $response->json('data.database');
            expect($database['status'])->toBe('connected');
            expect($database['driver'])->not()->toBeEmpty();
        });

        it('returns storage information', function () {
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/system-health');
            
            $response->assertOk();
            
            $storage = $response->json('data.storage');
            expect($storage)->toHaveKey('total');
            expect($storage)->toHaveKey('used');
            expect($storage)->toHaveKey('free');
            expect($storage)->toHaveKey('usage_percentage');
        });

        it('returns user statistics', function () {
            // Create additional users
            User::factory()->count(5)->create();
            
            $response = $this->actingAs($this->admin)
                ->getJson('/api/v1/admin/system-health');
            
            $response->assertOk();
            
            $users = $response->json('data.users');
            expect($users['total'])->toBe(7); // 5 + admin + user from beforeEach
            expect($users)->toHaveKey('active_today');
        });
    });
});

describe('AuditLog Model', function () {
    it('auto-generates UUID on create', function () {
        $log = AuditLog::factory()->create();
        
        expect($log->id)->not()->toBeNull();
        expect($log->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    it('casts old_values and new_values to array', function () {
        $log = AuditLog::factory()->create([
            'old_values' => ['key' => 'value'],
            'new_values' => ['key' => 'new_value'],
        ]);
        
        expect($log->old_values)->toBeArray();
        expect($log->new_values)->toBeArray();
    });

    it('calculates diff correctly', function () {
        $log = AuditLog::factory()->create([
            'old_values' => ['name' => 'Old', 'status' => 'active'],
            'new_values' => ['name' => 'New', 'status' => 'active'],
        ]);
        
        $diff = $log->diff;
        
        expect($diff)->toHaveKey('name');
        expect($diff['name']['old'])->toBe('Old');
        expect($diff['name']['new'])->toBe('New');
        expect($diff)->not()->toHaveKey('status'); // unchanged
    });

    it('scopes by user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        AuditLog::factory()->create(['user_id' => $user1->id]);
        AuditLog::factory()->create(['user_id' => $user2->id]);
        
        $logs = AuditLog::byUser($user1->id)->get();
        
        expect($logs)->toHaveCount(1);
        expect($logs->first()->user_id)->toBe($user1->id);
    });

    it('scopes by action', function () {
        AuditLog::factory()->create(['action' => 'create']);
        AuditLog::factory()->create(['action' => 'delete']);
        
        $logs = AuditLog::byAction('create')->get();
        
        expect($logs)->toHaveCount(1);
    });

    it('scopes by entity', function () {
        AuditLog::factory()->create(['entity_type' => 'Transaction', 'entity_id' => '1']);
        AuditLog::factory()->create(['entity_type' => 'Transaction', 'entity_id' => '2']);
        AuditLog::factory()->create(['entity_type' => 'User', 'entity_id' => '1']);
        
        $logs = AuditLog::byEntity('Transaction')->get();
        expect($logs)->toHaveCount(2);
        
        $logs = AuditLog::byEntity('Transaction', '1')->get();
        expect($logs)->toHaveCount(1);
    });

    it('scopes by date range', function () {
        AuditLog::factory()->create(['created_at' => now()->subDays(5)]);
        AuditLog::factory()->create(['created_at' => now()->subDays(10)]);
        
        $logs = AuditLog::inDateRange(
            now()->subDays(7)->format('Y-m-d'),
            now()->format('Y-m-d')
        )->get();
        
        expect($logs)->toHaveCount(1);
    });
});
