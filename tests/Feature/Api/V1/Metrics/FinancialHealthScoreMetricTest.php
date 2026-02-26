<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Budget\Models\Budget;
use Carbon\Carbon;

class FinancialHealthScoreMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/financial-health-score');
        $response->assertUnauthorized();
    }

    public function test_calculates_excellent_score(): void
    {
        $this->actingAs($this->user);

        // Emergency fund: 12 months covered (high score)
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 24000]);

        // Monthly expenses: 2000
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 2000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        // Income: 10000, Expenses: 4000 (good cash flow and savings rate)
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 10000]);

        $response = $this->getJson('/api/v1/metrics/financial-health-score?range=current-year');

        $response->assertOk();
        $score = $response->json('data.value');
        $this->assertGreaterThanOrEqual(70, $score);
        $this->assertContains($response->json('data.status'), ['Good', 'Excellent']);
        $this->assertArrayHasKey('components', $response->json('data'));
    }

    public function test_calculates_needs_attention_score(): void
    {
        $this->actingAs($this->user);

        // Low savings, negative cash flow
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 500]);
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 2000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 3000]);

        // Add some monthly expenses for emergency fund calc
        for ($i = 0; $i < 6; $i++) {
            Transaction::factory()->create([
                'brand_id' => $this->expensesBrand->id,
                'amount' => 1000,
                'created_at' => Carbon::now()->subMonths($i),
            ]);
        }

        $response = $this->getJson('/api/v1/metrics/financial-health-score?range=current-year');

        $response->assertOk();
        $score = $response->json('data.value');
        $this->assertLessThan(50, $score);
        $this->assertEquals('Needs Attention', $response->json('data.status'));
    }

    public function test_includes_component_scores(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 5000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 3000]);

        $response = $this->getJson('/api/v1/metrics/financial-health-score?range=current-year');

        $response->assertOk();
        $components = $response->json('data.components');
        $this->assertArrayHasKey('emergency_fund', $components);
        $this->assertArrayHasKey('cash_flow', $components);
        $this->assertArrayHasKey('savings_rate', $components);
        $this->assertArrayHasKey('budget_compliance', $components);
    }
}
