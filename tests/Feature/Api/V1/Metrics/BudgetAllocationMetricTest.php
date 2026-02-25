<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;

class BudgetAllocationMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/budget-allocation');
        $response->assertUnauthorized();
    }

    public function test_calculates_allocation_percentages(): void
    {
        $this->actingAs($this->user);

        // Income: 10000
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 10000]);
        // Expenses: 5000 (50%)
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 5000]);
        // Savings: 2000 (20%)
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 2000]);

        $response = $this->getJson('/api/v1/metrics/budget-allocation?range=current-year');

        $response->assertOk();
        $this->assertEquals(10000, $response->json('data.total_income'));
        $this->assertArrayHasKey('needs', $response->json('data'));
        $this->assertArrayHasKey('wants', $response->json('data'));
        $this->assertArrayHasKey('savings', $response->json('data'));
    }

    public function test_includes_recommendations(): void
    {
        $this->actingAs($this->user);

        // Low savings scenario
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 10000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 9000]);

        $response = $this->getJson('/api/v1/metrics/budget-allocation?range=current-year');

        $response->assertOk();
        $recommendations = $response->json('data.recommendations');
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
    }

    public function test_returns_zero_when_no_income(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/metrics/budget-allocation?range=current-year');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.value'));
        $this->assertEquals(0, $response->json('data.total_income'));
    }
}
