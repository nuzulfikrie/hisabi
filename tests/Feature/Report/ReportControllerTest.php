<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('displays reports page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Index')
        ->has('sections')
        ->has('currency')
        ->has('range')
        ->has('filters')
    );
});

it('requires authentication', function () {
    $response = $this->get(route('reports.index'));

    $response->assertRedirect(route('login'));
});

it('accepts date range filters', function () {
    $startDate = now()->subDays(30)->format('Y-m-d');
    $endDate = now()->format('Y-m-d');

    $response = $this->actingAs($this->user)
        ->get(route('reports.index', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Index')
        ->where('filters.start_date', $startDate)
        ->where('filters.end_date', $endDate)
        ->where('range', $startDate . ' - ' . $endDate)
    );
});

it('shows current month as default range when no dates provided', function () {
    $response = $this->actingAs($this->user)
        ->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Index')
        ->where('range', now()->format('F Y'))
        ->where('filters.start_date', null)
        ->where('filters.end_date', null)
    );
});
