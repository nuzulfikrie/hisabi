<?php

use App\Domains\Brand\Models\Brand;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    
    $this->incomeCategory = Category::factory()->create(['type' => Category::INCOME, 'name' => 'Salary']);
    $this->expensesCategory = Category::factory()->create(['type' => Category::EXPENSES, 'name' => 'Food']);
    $this->savingsCategory = Category::factory()->create(['type' => Category::SAVINGS, 'name' => 'Emergency Fund']);
    $this->investmentCategory = Category::factory()->create(['type' => Category::INVESTMENT, 'name' => 'Stocks']);
    
    $this->incomeBrand = Brand::factory()->create(['category_id' => $this->incomeCategory->id, 'name' => 'Company A']);
    $this->expensesBrand = Brand::factory()->create(['category_id' => $this->expensesCategory->id, 'name' => 'Restaurant']);
    $this->savingsBrand = Brand::factory()->create(['category_id' => $this->savingsCategory->id, 'name' => 'Bank']);
    $this->investmentBrand = Brand::factory()->create(['category_id' => $this->investmentCategory->id, 'name' => 'Broker']);
});

it('requires authentication', function () {
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    $response->assertUnauthorized();
});

it('returns projection data with default parameters', function () {
    $this->actingAs($this->user);
    
    // Create historical data for last 6 months
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'scenario',
                'projection_months',
                'current_net_worth',
                'monthly_averages' => [
                    'income',
                    'expenses',
                    'savings',
                ],
                'scenario_adjustments',
                'projected_net_worth',
                'projected_savings',
                'projected_expenses',
                'confidence_score' => [
                    'score',
                    'level',
                    'description',
                ],
                'recommendations',
                'historical_data',
            ]
        ]);
    
    expect($response->json('data.scenario'))->toBe('realistic');
    expect($response->json('data.projection_months'))->toBe(12);
    expect($response->json('data.current_net_worth'))->toBe(12000.0); // 6 months * (5000 - 3000)
});

it('supports conservative scenario', function () {
    $this->actingAs($this->user);
    
    // Create historical data
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection?scenario=conservative');
    
    $response->assertOk();
    expect($response->json('data.scenario'))->toBe('conservative');
    expect($response->json('data.scenario_adjustments.income_multiplier'))->toBe(0.9);
    expect($response->json('data.scenario_adjustments.adjusted_income'))->toBe(4500.0);
});

it('supports optimistic scenario', function () {
    $this->actingAs($this->user);
    
    // Create historical data
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection?scenario=optimistic');
    
    $response->assertOk();
    expect($response->json('data.scenario'))->toBe('optimistic');
    expect($response->json('data.scenario_adjustments.income_multiplier'))->toBe(1.1);
    expect($response->json('data.scenario_adjustments.adjusted_income'))->toBe(5500.0);
});

it('supports custom projection months', function () {
    $this->actingAs($this->user);
    
    // Create historical data
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection?months=6');
    
    $response->assertOk();
    expect($response->json('data.projection_months'))->toBe(6);
    expect(count($response->json('data.projected_net_worth')))->toBe(6);
});

it('calculates confidence score based on income stability', function () {
    $this->actingAs($this->user);
    
    // Create stable income data (low variance)
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000 + $i * 10, // Very stable, slight increase
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk();
    $confidenceScore = $response->json('data.confidence_score.score');
    expect($confidenceScore)->toBeGreaterThan(80); // High confidence for stable income
    expect($response->json('data.confidence_score.level'))->toBe('high');
});

it('provides warning recommendation for negative savings rate', function () {
    $this->actingAs($this->user);
    
    // Create data where expenses exceed income
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk();
    $recommendations = $response->json('data.recommendations');
    
    $hasNegativeSavingsWarning = false;
    foreach ($recommendations as $rec) {
        if ($rec['type'] === 'warning' && str_contains($rec['title'], 'Negative Savings')) {
            $hasNegativeSavingsWarning = true;
            break;
        }
    }
    
    expect($hasNegativeSavingsWarning)->toBeTrue();
});

it('provides success recommendation for high savings rate', function () {
    $this->actingAs($this->user);
    
    // Create data with high savings rate (>20%)
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 10000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk();
    $recommendations = $response->json('data.recommendations');
    
    $hasExcellentSavingsRecommendation = false;
    foreach ($recommendations as $rec) {
        if ($rec['type'] === 'success' && str_contains($rec['title'], 'Excellent Savings')) {
            $hasExcellentSavingsRecommendation = true;
            break;
        }
    }
    
    expect($hasExcellentSavingsRecommendation)->toBeTrue();
});

it('includes historical data for last 6 months', function () {
    $this->actingAs($this->user);
    
    // Create historical data
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk();
    $historicalData = $response->json('data.historical_data');
    
    expect(count($historicalData))->toBe(6);
    expect($historicalData[0])->toHaveKeys(['month', 'income', 'expenses']);
});

it('returns zero values when no transaction data exists', function () {
    $this->actingAs($this->user);
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk();
    expect($response->json('data.current_net_worth'))->toBe(0.0);
    expect($response->json('data.monthly_averages.income'))->toBe(0.0);
    expect($response->json('data.monthly_averages.expenses'))->toBe(0.0);
});

it('projections include confidence bands', function () {
    $this->actingAs($this->user);
    
    // Create historical data
    for ($i = 0; $i < 6; $i++) {
        Transaction::factory()->create([
            'brand_id' => $this->incomeBrand->id,
            'amount' => 5000,
            'created_at' => now()->subMonths($i),
        ]);
        Transaction::factory()->create([
            'brand_id' => $this->expensesBrand->id,
            'amount' => 3000,
            'created_at' => now()->subMonths($i),
        ]);
    }
    
    $response = $this->getJson('/api/v1/metrics/financial-projection');
    
    $response->assertOk();
    $firstProjection = $response->json('data.projected_net_worth')[0];
    
    expect($firstProjection)->toHaveKeys(['month', 'value', 'lower_bound', 'upper_bound']);
    expect($firstProjection['lower_bound'])->toBeLessThan($firstProjection['value']);
    expect($firstProjection['upper_bound'])->toBeGreaterThan($firstProjection['value']);
});
