<?php

namespace Tests\Feature\Api\V1\Metrics;

use App\Domains\Transaction\Models\Transaction;

class CashFlowMetricTest extends MetricsTestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/cash-flow');
        $response->assertUnauthorized();
    }

    public function test_calculates_positive_cash_flow(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 5000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 2000]);

        $response = $this->getJson('/api/v1/metrics/cash-flow?range=current-year');

        $response->assertOk();
        $this->assertEquals(3000, $response->json('data.value'));
        $this->assertEquals(5000, $response->json('data.income'));
        $this->assertEquals(2000, $response->json('data.expenses'));
    }

    public function test_calculates_negative_cash_flow(): void
    {
        $this->actingAs($this->user);

        Transaction::factory()->create(['brand_id' => $this->incomeBrand->id, 'amount' => 2000]);
        Transaction::factory()->create(['brand_id' => $this->expensesBrand->id, 'amount' => 3000]);

        $response = $this->getJson('/api/v1/metrics/cash-flow?range=current-year');

        $response->assertOk();
        $this->assertEquals(-1000, $response->json('data.value'));
    }

    public function test_returns_zero_when_no_data(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/v1/metrics/cash-flow?range=current-year');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.value'));
    }
}
