<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;

class TopExpensesMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/top-expenses');
        $response->assertUnauthorized();
    }

    public function test_returns_top_expenses_ordered_by_amount(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 500]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 1000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 200]);

        $response = $this->getJson('/api/v1/metrics/top-expenses?range=current-year');

        $response->assertOk();
        $expenses = $response->json('data.expenses');
        $this->assertCount(3, $expenses);
        $this->assertEquals(1000, $expenses[0]['amount']);
        $this->assertEquals(500, $expenses[1]['amount']);
        $this->assertEquals(200, $expenses[2]['amount']);
    }

    public function test_limits_to_ten_expenses(): void
    {
        $this->actingAs($this->user);

        // Create 15 transactions
        for ($i = 1; $i <= 15; $i++) {
            Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => $i * 100]);
        }

        $response = $this->getJson('/api/v1/metrics/top-expenses?range=current-year');

        $response->assertOk();
        $expenses = $response->json('data.expenses');
        $this->assertCount(10, $expenses);
    }

    public function test_excludes_non_expense_transactions(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 500]);
        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 5000]);
        Transaction::factory()->create(['brand_id' => $this->savingsBrand->id, 'amount' => 1000]);

        $response = $this->getJson('/api/v1/metrics/top-expenses?range=current-year');

        $response->assertOk();
        $expenses = $response->json('data.expenses');
        $this->assertCount(1, $expenses);
        $this->assertEquals(500, $expenses[0]['amount']);
    }

    public function test_includes_brand_and_category_info(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 500]);

        $response = $this->getJson('/api/v1/metrics/top-expenses?range=current-year');

        $response->assertOk();
        $expense = $response->json('data.expenses')[0];
        $this->assertArrayHasKey('brand_name', $expense);
        $this->assertArrayHasKey('category_name', $expense);
        $this->assertArrayHasKey('date', $expense);
    }
}
