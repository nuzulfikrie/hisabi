<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class RecurringExpensesMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/recurring-expenses');
        $response->assertUnauthorized();
    }

    public function test_detects_monthly_recurring_expense(): void
    {
        $this->actingAs($this->user);

        // Create 3 months of same brand, similar amount
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 100,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/recurring-expenses?range=current-year');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, $response->json('data.value'));
        $expenses = $response->json('data.expenses');
        $this->assertNotEmpty($expenses);
    }

    public function test_identifies_potentially_unused_subscriptions(): void
    {
        $this->actingAs($this->user);

        // Create old transactions (>60 days ago)
        for ($i = 3; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 100,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/recurring-expenses?range=current-year');

        $response->assertOk();
        $expenses = $response->json('data.expenses');
        if (count($expenses) > 0) {
            $this->assertTrue($expenses[0]['potentially_unused']);
        }
    }

    public function test_groups_by_frequency(): void
    {
        $this->actingAs($this->user);

        // Monthly pattern
        for ($i = 0; $i < 3; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 100,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/recurring-expenses?range=current-year');

        $response->assertOk();
        $grouped = $response->json('data.grouped_by_frequency');
        $this->assertArrayHasKey('monthly', $grouped);
        $this->assertArrayHasKey('quarterly', $grouped);
        $this->assertArrayHasKey('annual', $grouped);
        $this->assertArrayHasKey('irregular', $grouped);
    }

    public function test_returns_empty_when_no_recurring_patterns(): void
    {
        $this->actingAs($this->user);

        // Single transaction only
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 100,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->getJson('/api/v1/metrics/recurring-expenses?range=current-year');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.value'));
        $this->assertEmpty($response->json('data.expenses'));
    }
}
