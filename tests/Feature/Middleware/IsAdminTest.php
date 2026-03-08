<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin users through', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertSuccessful();
});

it('blocks regular users', function () {
    $user = User::factory()->create(['role' => UserRole::USER]);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('blocks accountant users', function () {
    $accountant = User::factory()->accountant()->create();

    $this->actingAs($accountant)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});
