<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('downloads report as xlsx', function () {
    $response = $this->actingAs($this->user)
        ->get(route('exports.report', ['format' => 'xlsx']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->assertHeader('Content-Disposition');
});

it('downloads report as csv', function () {
    $response = $this->actingAs($this->user)
        ->get(route('exports.report', ['format' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type');
});

it('requires authentication', function () {
    $response = $this->get(route('exports.report'));

    $response->assertRedirect(route('login'));
});

it('validates date range', function () {
    $response = $this->actingAs($this->user)
        ->get(route('exports.report', [
            'format' => 'xlsx',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->subDays(10)->format('Y-m-d'),
        ]));

    $response->assertSessionHasErrors('end_date');
});

it('validates format parameter', function () {
    $response = $this->actingAs($this->user)
        ->get(route('exports.report', ['format' => 'invalid']));

    $response->assertSessionHasErrors('format');
});

it('accepts valid date range filters', function () {
    $startDate = now()->subDays(30)->format('Y-m-d');
    $endDate = now()->format('Y-m-d');

    $response = $this->actingAs($this->user)
        ->get(route('exports.report', [
            'format' => 'csv',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

    $response->assertOk();
    $response->assertHeader('Content-Type');
});
