<?php

use App\Domains\Tag\Models\Tag;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('GET /api/v1/tags', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/tags');
        $response->assertStatus(401);
    });

    it('returns paginated tags', function () {
        Tag::factory()->count(10)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'color',
                        'transactionsCount',
                    ],
                ],
                'paginatorInfo' => [
                    'hasMorePages',
                    'currentPage',
                    'lastPage',
                    'perPage',
                    'total',
                ],
            ]);

        expect($response->json('paginatorInfo.total'))->toBe(10);
    });

    it('only returns tags for authenticated user', function () {
        $otherUser = User::factory()->create();
        Tag::factory()->count(5)->create(['user_id' => $this->user->id]);
        Tag::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags');

        expect($response->json('paginatorInfo.total'))->toBe(5);
    });

    it('filters tags by search', function () {
        Tag::factory()->create(['name' => 'Food', 'user_id' => $this->user->id]);
        Tag::factory()->create(['name' => 'Travel', 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?filter[search]=Food');

        $response->assertStatus(200);
        expect($response->json('paginatorInfo.total'))->toBe(1);
        expect($response->json('data.0.name'))->toBe('Food');
    });

    it('respects per page parameter', function () {
        Tag::factory()->count(30)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags?perPage=10');

        $response->assertStatus(200);
        expect($response->json('paginatorInfo.perPage'))->toBe(10);
        expect(count($response->json('data')))->toBe(10);
    });
});

describe('GET /api/v1/tags/all', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/tags/all');
        $response->assertStatus(401);
    });

    it('returns all tags without pagination', function () {
        Tag::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags/all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'color',
                        'transactionsCount',
                    ],
                ],
            ]);

        expect(count($response->json('data')))->toBe(5);
    });
});

describe('POST /api/v1/tags', function () {
    it('requires authentication', function () {
        $response = $this->postJson('/api/v1/tags', []);
        $response->assertStatus(401);
    });

    it('creates a tag', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', [
                'name' => 'Test Tag',
                'color' => '#FF5733',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'tag' => [
                    'uuid',
                    'name',
                    'color',
                    'transactionsCount',
                ],
            ])
            ->assertJsonPath('tag.name', 'Test Tag')
            ->assertJsonPath('tag.color', '#FF5733');

        $this->assertDatabaseHas('tags', [
            'name' => 'Test Tag',
            'color' => '#FF5733',
            'user_id' => $this->user->id,
        ]);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color']);
    });

    it('validates color format', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', [
                'name' => 'Test Tag',
                'color' => 'invalid-color',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    });

    it('assigns tag to authenticated user', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/tags', [
                'name' => 'My Tag',
                'color' => '#00FF00',
            ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'My Tag',
            'user_id' => $this->user->id,
        ]);
    });
});

describe('PUT /api/v1/tags/{uuid}', function () {
    it('requires authentication', function () {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $response = $this->putJson("/api/v1/tags/{$tag->uuid}", []);
        $response->assertStatus(401);
    });

    it('updates a tag', function () {
        $tag = Tag::factory()->create([
            'name' => 'Old Name',
            'color' => '#FF0000',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tags/{$tag->uuid}", [
                'name' => 'New Name',
                'color' => '#00FF00',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('tag.name', 'New Name')
            ->assertJsonPath('tag.color', '#00FF00');

        $this->assertDatabaseHas('tags', [
            'uuid' => $tag->uuid,
            'name' => 'New Name',
            'color' => '#00FF00',
        ]);
    });

    it('validates required fields', function () {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tags/{$tag->uuid}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'color']);
    });

    it('returns 404 for non-existent tag', function () {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/tags/non-existent-uuid', [
                'name' => 'Test',
                'color' => '#FF0000',
            ]);

        $response->assertStatus(404);
    });

    it('cannot update another user\'s tag', function () {
        $otherUser = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/tags/{$tag->uuid}", [
                'name' => 'Hacked',
                'color' => '#FF0000',
            ]);

        $response->assertStatus(404);
    });
});

describe('DELETE /api/v1/tags/{uuid}', function () {
    it('requires authentication', function () {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $response = $this->deleteJson("/api/v1/tags/{$tag->uuid}");
        $response->assertStatus(401);
    });

    it('deletes a tag', function () {
        $tag = Tag::factory()->create([
            'name' => 'Tag to Delete',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/tags/{$tag->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('tag.uuid', $tag->uuid)
            ->assertJsonPath('tag.name', 'Tag to Delete');

        $this->assertDatabaseMissing('tags', [
            'uuid' => $tag->uuid,
        ]);
    });

    it('returns 404 for non-existent tag', function () {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/tags/non-existent-uuid');

        $response->assertStatus(404);
    });

    it('cannot delete another user\'s tag', function () {
        $otherUser = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/tags/{$tag->uuid}");

        $response->assertStatus(404);
    });
});

describe('GET /api/v1/tags/{uuid}/transactions', function () {
    it('requires authentication', function () {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $response = $this->getJson("/api/v1/tags/{$tag->uuid}/transactions");
        $response->assertStatus(401);
    });

    it('returns transactions for a tag', function () {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $transaction = Transaction::factory()->create();
        $tag->transactions()->attach($transaction->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/tags/{$tag->uuid}/transactions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'note',
                        'created_at',
                        'brand',
                        'category',
                    ],
                ],
                'paginatorInfo' => [
                    'hasMorePages',
                    'currentPage',
                    'lastPage',
                    'perPage',
                    'total',
                ],
            ]);

        expect($response->json('paginatorInfo.total'))->toBe(1);
    });

    it('returns 404 for non-existent tag', function () {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/tags/non-existent-uuid/transactions');

        $response->assertStatus(404);
    });
});

describe('Transaction-Tag Integration', function () {
    it('can attach tags when creating a transaction', function () {
        $tag = Tag::factory()->create(['user_id' => $this->user->id]);
        $brand = \App\Domains\Brand\Models\Brand::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/transactions', [
                'amount' => 100,
                'brand_id' => $brand->id,
                'created_at' => now()->format('Y-m-d'),
                'tags' => [$tag->uuid],
            ]);

        $response->assertStatus(201);
        
        $transactionId = $response->json('transaction.id');
        $this->assertDatabaseHas('tag_transaction', [
            'tag_uuid' => $tag->uuid,
            'transaction_id' => $transactionId,
        ]);
    });

    it('can sync tags when updating a transaction', function () {
        $tag1 = Tag::factory()->create(['user_id' => $this->user->id]);
        $tag2 = Tag::factory()->create(['user_id' => $this->user->id]);
        $transaction = Transaction::factory()->create();
        $transaction->syncTags([$tag1->uuid]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/transactions/{$transaction->id}", [
                'amount' => $transaction->amount,
                'brand_id' => $transaction->brand_id,
                'created_at' => $transaction->created_at->format('Y-m-d'),
                'tags' => [$tag2->uuid],
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('tag_transaction', [
            'tag_uuid' => $tag1->uuid,
            'transaction_id' => $transaction->id,
        ]);
        $this->assertDatabaseHas('tag_transaction', [
            'tag_uuid' => $tag2->uuid,
            'transaction_id' => $transaction->id,
        ]);
    });

    it('includes tags in transaction response', function () {
        $tag = Tag::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Tag',
            'color' => '#FF0000',
        ]);
        $transaction = Transaction::factory()->create();
        $transaction->attachTag($tag);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.tags.0.uuid', $tag->uuid)
            ->assertJsonPath('data.0.tags.0.name', 'Test Tag')
            ->assertJsonPath('data.0.tags.0.color', '#FF0000');
    });
});
