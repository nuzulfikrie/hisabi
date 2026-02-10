<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

it('requires authentication to access settings', function () {
    $this->get(route('admin.settings.index'))
        ->assertRedirect(route('login'));
});

it('denies access to non-admin users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});

it('shows settings page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->has('settings')
            ->has('groups')
            ->has('currentGroup')
        );
});

it('filters settings by group', function () {
    Setting::create([
        'key' => 'site_name',
        'name' => 'Site Name',
        'value' => 'Hisabi',
        'type' => 'string',
        'group' => 'general',
    ]);
    Setting::create([
        'key' => 'smtp_host',
        'name' => 'SMTP Host',
        'value' => 'localhost',
        'type' => 'string',
        'group' => 'email',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.settings.index', ['group' => 'general']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('currentGroup', 'general')
        );
});

it('updates settings', function () {
    Setting::create([
        'key' => 'site_name',
        'name' => 'Site Name',
        'value' => 'Hisabi',
        'type' => 'string',
        'group' => 'general',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.settings.update'), [
            'settings' => [
                'site_name' => 'Hisabi Updated',
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('settings', [
        'key' => 'site_name',
        'value' => 'Hisabi Updated',
    ]);
});

it('validates settings payload is required', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.settings.update'), [])
        ->assertSessionHasErrors(['settings']);
});

it('validates settings must be an array', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.settings.update'), [
            'settings' => 'not-an-array',
        ])
        ->assertSessionHasErrors(['settings']);
});

it('denies settings update for non-admin', function () {
    $this->actingAs($this->user)
        ->post(route('admin.settings.update'), [
            'settings' => ['key' => 'value'],
        ])
        ->assertForbidden();
});
