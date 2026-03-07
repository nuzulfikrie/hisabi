<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class CashRunwayMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/cash-runway');
        $response->assertUnauthorized();
    }

    public function test_calculates_runway_correctly(): void
    {
        $this->actingAs($this->user);

        // Savings: 12000
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 12000]);

        // Monthly expenses: 2000
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/cash-runway?range=current-year');

        $response->assertOk();
        $this->assertEquals(6.0, $response->json('data.value')); // 12000 / 2000
        $this->assertEquals('green', $response->json('data.status'));
        $this->assertFalse($response->json('data.needs_attention'));
    }

    public function test_returns_red_for_low_runway(): void
    {
        $this->actingAs($this->user);

        // Savings: 3000
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 3000]);

        // Monthly expenses: 2000
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/cash-runway?range=current-year');

        $response->assertOk();
        $this->assertEquals(1.5, $response->json('data.value'));
        $this->assertEquals('red', $response->json('data.status'));
        $this->assertTrue($response->json('data.needs_attention'));
    }

    public function test_includes_burn_rate_calculations(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 6000]);

        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/cash-runway?range=current-year');

        $response->assertOk();
        $this->assertEquals(2000, $response->json('data.monthly_burn_rate'));
        $this->assertArrayHasKey('daily_burn_rate', $response->json('data'));
        $this->assertArrayHasKey('runway_days', $response->json('data'));
    }
}
