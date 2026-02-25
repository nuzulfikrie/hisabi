<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;

class SavingsRateMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/savings-rate');
        $response->assertUnauthorized();
    }

    public function test_calculates_savings_rate_correctly(): void
    {
        $this->actingAs($this->user);

        // Income: 5000, Expenses: 3000, Savings: 2000 (40%)
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 5000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 3000]);

        $response = $this->getJson('/api/v1/metrics/savings-rate?range=current-year');

        $response->assertOk();
        $this->assertEquals(40.0, $response->json('data.value'));
        $this->assertEquals(20.0, $response->json('data.target_rate'));
        $this->assertEquals(0, $response->json('data.gap_to_target'));
        $this->assertEquals('green', $response->json('data.status'));
    }

    public function test_returns_yellow_status_for_15_percent_savings(): void
    {
        $this->actingAs($this->user);

        // Income: 10000, Expenses: 8500, Savings: 1500 (15%)
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 10000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 8500]);

        $response = $this->getJson('/api/v1/metrics/savings-rate?range=current-year');

        $response->assertOk();
        $this->assertEquals(15.0, $response->json('data.value'));
        $this->assertEquals(5.0, $response->json('data.gap_to_target'));
        $this->assertEquals('yellow', $response->json('data.status'));
    }

    public function test_returns_red_status_for_low_savings(): void
    {
        $this->actingAs($this->user);

        // Income: 5000, Expenses: 4800, Savings: 200 (4%)
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 5000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 4800]);

        $response = $this->getJson('/api/v1/metrics/savings-rate?range=current-year');

        $response->assertOk();
        $this->assertEquals(4.0, $response->json('data.value'));
        $this->assertEquals('red', $response->json('data.status'));
    }

    public function test_returns_zero_when_no_income(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/metrics/savings-rate?range=current-year');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.value'));
    }
}
