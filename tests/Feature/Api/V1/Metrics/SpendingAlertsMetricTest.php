<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Budget\Models\Budget;
use Carbon\Carbon;

class SpendingAlertsMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/spending-alerts');
        $response->assertUnauthorized();
    }

    public function test_detects_spending_increase_warning(): void
    {
        $this->actingAs($this->user);

        // Last month spending: 1000
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 1000,
            'created_at' => Carbon::now()->subMonth(),
        ]);

        // Current month spending: 1400 (40% increase)
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 1400,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->getJson('/api/v1/metrics/spending-alerts?range=current-year');

        $response->assertOk();
        $alerts = $response->json('data.alerts');
        $this->assertGreaterThanOrEqual(1, count($alerts));
        $this->assertTrue(collect($alerts)->contains('severity', 'warning'));
    }

    public function test_detects_budget_warning(): void
    {
        $this->actingAs($this->user);

        // Create a budget at 85% spent
        $budget = Budget::factory()->create([
            'name' => 'Test Budget',
            'amount' => 1000,
            'reoccurrence' => Budget::MONTHLY,
            'period' => 1,
            'start_at' => Carbon::now()->startOfMonth(),
            'end_at' => Carbon::now()->endOfMonth(),
        ]);
        $budget->categories()->attach($this->expensesCategory->id);

        // Add spending to reach 85%
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 850,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->getJson('/api/v1/metrics/spending-alerts?range=current-year');

        $response->assertOk();
        $alerts = $response->json('data.alerts');
        $this->assertTrue(collect($alerts)->contains('type', 'budget_warning'));
    }

    public function test_returns_empty_when_no_alerts(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/metrics/spending-alerts?range=current-year');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.value'));
        $this->assertEmpty($response->json('data.alerts'));
    }
}
