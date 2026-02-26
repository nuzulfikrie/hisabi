<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class EmergencyFundStatusMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/emergency-fund-status');
        $response->assertUnauthorized();
    }

    public function test_calculates_months_covered(): void
    {
        $this->actingAs($this->user);

        // Savings: 12000
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 12000]);

        // Monthly expenses: 2000/month for last 6 months
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/emergency-fund-status?range=current-year');

        $response->assertOk();
        $this->assertEquals(6.0, $response->json('data.value')); // 12000 / 2000 = 6 months
        $this->assertEquals('green', $response->json('data.status'));
        $this->assertEquals(12000, $response->json('data.total_savings'));
    }

    public function test_returns_yellow_for_three_to_six_months(): void
    {
        $this->actingAs($this->user);

        // Savings: 6000
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 6000]);

        // Monthly expenses: 2000/month
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/emergency-fund-status?range=current-year');

        $response->assertOk();
        $this->assertEquals(3.0, $response->json('data.value'));
        $this->assertEquals('yellow', $response->json('data.status'));
    }

    public function test_returns_red_for_less_than_three_months(): void
    {
        $this->actingAs($this->user);

        // Savings: 3000
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 3000]);

        // Monthly expenses: 2000/month
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/emergency-fund-status?range=current-year');

        $response->assertOk();
        $this->assertEquals(1.5, $response->json('data.value'));
        $this->assertEquals('red', $response->json('data.status'));
    }

    public function test_calculates_gap_to_targets(): void
    {
        $this->actingAs($this->user);

        // Savings: 3000
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 3000]);

        // Monthly expenses: 2000/month
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/emergency-fund-status?range=current-year');

        $response->assertOk();
        // Gap to 3 months: (3 * 2000) - 3000 = 3000
        $this->assertEquals(3000, $response->json('data.gap_to_3_month'));
        // Gap to 6 months: (6 * 2000) - 3000 = 9000
        $this->assertEquals(9000, $response->json('data.gap_to_6_month'));
    }
}
