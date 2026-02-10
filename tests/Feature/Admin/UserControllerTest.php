<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

it('requires authentication to access user management', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

it('denies access to non-admin users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('allows admin to view user list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertSuccessful();
});

it('returns paginated users for admin', function () {
    User::factory()->count(5)->create();

    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users')
            ->has('statuses')
            ->has('roles')
        );
});

it('filters users by name', function () {
    User::factory()->create(['name' => 'John Doe']);
    User::factory()->create(['name' => 'Jane Smith']);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['name' => 'John']));

    $response->assertSuccessful();
});

it('filters users by status', function () {
    User::factory()->create(['status' => UserStatus::ACTIVE]);
    User::factory()->inactive()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['status' => 'active']));

    $response->assertSuccessful();
});

it('filters users by role', function () {
    User::factory()->admin()->create();
    User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['role' => 'admin']));

    $response->assertSuccessful();
});

it('shows create user form for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Create')
            ->has('roles')
            ->has('statuses')
        );
});

it('stores a new user', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'role' => UserRole::USER->value,
        'status' => UserStatus::ACTIVE->value,
    ]);
});

it('validates required fields when creating user', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role', 'status']);
});

it('validates unique email when creating user', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ])
        ->assertSessionHasErrors(['email']);
});

it('shows user details', function () {
    $targetUser = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.users.show', $targetUser))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->has('user')
            ->has('telegramTransactions')
        );
});

it('shows edit user form for admin', function () {
    $targetUser = User::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.users.edit', $targetUser))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Edit')
            ->has('user')
            ->has('roles')
            ->has('statuses')
        );
});

it('updates a user', function () {
    $targetUser = User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->put(route('admin.users.update', $targetUser), [
            'name' => 'Updated Name',
            'email' => $targetUser->email,
            'role' => UserRole::ACCOUNTANT->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

    $response->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'name' => 'Updated Name',
        'role' => UserRole::ACCOUNTANT->value,
    ]);
});

it('updates user password when provided', function () {
    $targetUser = User::factory()->create();
    $oldPassword = $targetUser->password;

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'password' => 'newpassword123',
            'role' => $targetUser->role->value,
            'status' => $targetUser->status->value,
        ]);

    $targetUser->refresh();
    expect($targetUser->password)->not->toBe($oldPassword);
});

it('does not update password when not provided', function () {
    $targetUser = User::factory()->create();
    $oldPassword = $targetUser->password;

    $this->actingAs($this->admin)
        ->put(route('admin.users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => $targetUser->role->value,
            'status' => $targetUser->status->value,
        ]);

    $targetUser->refresh();
    expect($targetUser->password)->toBe($oldPassword);
});

it('deletes a user', function () {
    $targetUser = User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $targetUser));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
});

it('prevents admin from deleting themselves', function () {
    $response = $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $this->admin));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

it('toggles user status from active to inactive', function () {
    $targetUser = User::factory()->create(['status' => UserStatus::ACTIVE]);

    $this->actingAs($this->admin)
        ->post(route('admin.users.toggle-status', $targetUser));

    $targetUser->refresh();
    expect($targetUser->status)->toBe(UserStatus::INACTIVE);
});

it('toggles user status from inactive to active', function () {
    $targetUser = User::factory()->inactive()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.toggle-status', $targetUser));

    $targetUser->refresh();
    expect($targetUser->status)->toBe(UserStatus::ACTIVE);
});

it('prevents admin from toggling own status', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.toggle-status', $this->admin))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('disconnects telegram account', function () {
    $targetUser = User::factory()->withTelegram()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.users.disconnect-telegram', $targetUser));

    $targetUser->refresh();
    expect($targetUser->telegram_chat_id)->toBeNull();
    expect($targetUser->telegram_username)->toBeNull();
    expect($targetUser->telegram_verified_at)->toBeNull();
});

it('denies non-admin access to store', function () {
    $this->actingAs($this->user)
        ->post(route('admin.users.store'), [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'role' => UserRole::USER->value,
            'status' => UserStatus::ACTIVE->value,
        ])
        ->assertForbidden();
});
