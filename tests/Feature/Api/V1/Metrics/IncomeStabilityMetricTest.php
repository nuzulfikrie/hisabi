<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class IncomeStabilityMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/income-stability');
        $response->assertUnauthorized();
    }

    public function test_detects_stable_income(): void
    {
        $this->actingAs($this->user);

        // Create 6 months of stable income (little variation)
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->incomeBrand->id,
                'amount' => 5000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/income-stability?range=current-year');

        $response->assertOk();
        $this->assertEquals('Stable', $response->json('data.stability_score'));
        $this->assertEquals('green', $response->json('data.status'));
    }

    public function test_detects_variable_income(): void
    {
        $this->actingAs($this->user);

        // Create 6 months of variable income (high variation)
        $amounts = [3000, 8000, 2000, 9000, 4000, 7000];
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->incomeBrand->id,
                'amount' => $amounts[$i],
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/income-stability?range=current-year');

        $response->assertOk();
        // Should be Variable or Highly Variable due to high CV
        $this->assertContains($response->json('data.stability_score'), ['Variable', 'Highly Variable']);
    }

    public function test_includes_income_statistics(): void
    {
        $this->actingAs($this->user);

        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->incomeBrand->id,
                'amount' => 5000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/income-stability?range=current-year');

        $response->assertOk();
        $this->assertArrayHasKey('average_income', $response->json('data'));
        $this->assertArrayHasKey('min_income', $response->json('data'));
        $this->assertArrayHasKey('max_income', $response->json('data'));
        $this->assertArrayHasKey('standard_deviation', $response->json('data'));
    }

    public function test_returns_unknown_with_insufficient_data(): void
    {
        $this->actingAs($this->user);

        // Only 1 month of data
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->getJson('/api/v1/metrics/income-stability?range=current-year');

        $response->assertOk();
        $this->assertEquals('Unknown', $response->json('data.stability_score'));
    }
}
