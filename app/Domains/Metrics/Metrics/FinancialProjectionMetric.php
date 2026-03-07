<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class FinancialProjectionMetric extends Metric
{
    private int $projectionMonths;
    private string $scenario;

    public function __construct(?string $from = null, ?string $to = null, int $projectionMonths = 12, string $scenario = 'realistic')
    {
        parent::__construct($from, $to);
        $this->projectionMonths = $projectionMonths;
        $this->scenario = in_array($scenario, ['conservative', 'optimistic', 'realistic']) ? $scenario : 'realistic';
    }

    public function calculate(): array
    {
        // Get historical data for last 6 months
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        
        $monthlyIncome = Transaction::query()
            ->income()
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('SUM(amount) as total, ' . $this->getDateFormat('%Y-%m') . ' as month')
            ->groupBy('month')
            ->pluck('total')
            ->toArray();

        $monthlyExpenses = Transaction::query()
            ->expenses()
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('SUM(amount) as total, ' . $this->getDateFormat('%Y-%m') . ' as month')
            ->groupBy('month')
            ->pluck('total')
            ->toArray();

        // Calculate averages
        $avgIncome = count($monthlyIncome) > 0 ? array_sum($monthlyIncome) / count($monthlyIncome) : 0;
        $avgExpenses = count($monthlyExpenses) > 0 ? array_sum($monthlyExpenses) / count($monthlyExpenses) : 0;

        // Calculate scenario-adjusted values
        $incomeMultiplier = $this->getIncomeMultiplier();
        $adjustedIncome = $avgIncome * $incomeMultiplier;
        $monthlySavings = $adjustedIncome - $avgExpenses;

        // Get current net worth
        $totalIncome = Transaction::income()->sum('amount');
        $totalExpenses = Transaction::expenses()->sum('amount');
        $currentNetWorth = $totalIncome - $totalExpenses;

        // Calculate income stability for confidence score
        $confidenceScore = $this->calculateConfidenceScore($monthlyIncome);

        // Generate projections
        $projections = $this->generateProjections(
            $currentNetWorth,
            $monthlySavings,
            $adjustedIncome,
            $avgExpenses,
            $confidenceScore
        );

        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $monthlySavings,
            $adjustedIncome,
            $avgExpenses,
            $confidenceScore
        );

        return [
            'scenario' => $this->scenario,
            'projection_months' => $this->projectionMonths,
            'current_net_worth' => round($currentNetWorth, 2),
            'monthly_averages' => [
                'income' => round($avgIncome, 2),
                'expenses' => round($avgExpenses, 2),
                'savings' => round($avgIncome - $avgExpenses, 2),
            ],
            'scenario_adjustments' => [
                'income_multiplier' => $incomeMultiplier,
                'adjusted_income' => round($adjustedIncome, 2),
            ],
            'projected_net_worth' => $projections['net_worth'],
            'projected_savings' => $projections['savings'],
            'projected_expenses' => $projections['expenses'],
            'confidence_score' => $confidenceScore,
            'recommendations' => $recommendations,
            'historical_data' => $this->formatHistoricalData($monthlyIncome, $monthlyExpenses),
        ];
    }

    private function getIncomeMultiplier(): float
    {
        return match ($this->scenario) {
            'conservative' => 0.9,  // 10% lower income estimate
            'optimistic' => 1.1,    // 10% higher income estimate
            'realistic' => 1.0,     // Average income
            default => 1.0,
        };
    }

    private function calculateConfidenceScore(array $monthlyIncome): array
    {
        $monthCount = count($monthlyIncome);
        
        if ($monthCount < 2) {
            return [
                'score' => 0,
                'level' => 'unknown',
                'description' => 'Insufficient data for confidence calculation',
            ];
        }

        $avgIncome = array_sum($monthlyIncome) / $monthCount;
        
        if ($avgIncome <= 0) {
            return [
                'score' => 0,
                'level' => 'unknown',
                'description' => 'No income data available',
            ];
        }

        // Calculate standard deviation
        $variance = 0;
        foreach ($monthlyIncome as $income) {
            $variance += pow($income - $avgIncome, 2);
        }
        $variance = $variance / $monthCount;
        $stdDev = sqrt($variance);

        // Coefficient of variation
        $cv = ($stdDev / $avgIncome) * 100;

        // Convert CV to confidence score (0-100)
        // Lower CV = higher confidence
        $score = max(0, min(100, 100 - $cv));

        $level = match (true) {
            $score >= 80 => 'high',
            $score >= 50 => 'medium',
            $score >= 20 => 'low',
            default => 'very_low',
        };

        $description = match ($level) {
            'high' => 'Your income is stable and projections are highly reliable',
            'medium' => 'Your income has moderate variability; projections are reasonably reliable',
            'low' => 'Your income is variable; projections should be used as estimates only',
            'very_low' => 'High income variability; projections are speculative',
            default => 'Unable to determine confidence level',
        };

        return [
            'score' => round($score, 2),
            'level' => $level,
            'coefficient_of_variation' => round($cv, 2),
            'description' => $description,
            'months_of_data' => $monthCount,
        ];
    }

    private function generateProjections(
        float $currentNetWorth,
        float $monthlySavings,
        float $monthlyIncome,
        float $monthlyExpenses,
        array $confidenceScore
    ): array {
        $netWorthProjections = [];
        $savingsProjections = [];
        $expensesProjections = [];

        $runningNetWorth = $currentNetWorth;
        $currentDate = Carbon::now();

        for ($i = 1; $i <= $this->projectionMonths; $i++) {
            $projectionDate = $currentDate->copy()->addMonths($i);
            $monthKey = $projectionDate->format('Y-m');
            
            $runningNetWorth += $monthlySavings;
            
            // Apply confidence bands (wider bands = lower confidence)
            $confidenceFactor = (100 - $confidenceScore['score']) / 100;
            $variance = abs($monthlySavings) * $confidenceFactor * 0.5;

            $netWorthProjections[] = [
                'month' => $monthKey,
                'value' => round($runningNetWorth, 2),
                'lower_bound' => round($runningNetWorth - $variance * $i, 2),
                'upper_bound' => round($runningNetWorth + $variance * $i, 2),
            ];

            $savingsProjections[] = [
                'month' => $monthKey,
                'value' => round($monthlySavings, 2),
            ];

            $expensesProjections[] = [
                'month' => $monthKey,
                'value' => round($monthlyExpenses, 2),
            ];
        }

        return [
            'net_worth' => $netWorthProjections,
            'savings' => $savingsProjections,
            'expenses' => $expensesProjections,
        ];
    }

    private function generateRecommendations(
        float $monthlySavings,
        float $monthlyIncome,
        float $monthlyExpenses,
        array $confidenceScore
    ): array {
        $recommendations = [];

        // Savings rate recommendation
        $savingsRate = $monthlyIncome > 0 ? ($monthlySavings / $monthlyIncome) * 100 : 0;
        
        if ($savingsRate < 0) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Negative Savings Rate',
                'message' => 'Your expenses exceed your income. Consider reducing expenses or increasing income to avoid debt.',
            ];
        } elseif ($savingsRate < 10) {
            $recommendations[] = [
                'type' => 'caution',
                'title' => 'Low Savings Rate',
                'message' => 'Aim to save at least 10% of your income for financial security.',
            ];
        } elseif ($savingsRate >= 20) {
            $recommendations[] = [
                'type' => 'success',
                'title' => 'Excellent Savings Rate',
                'message' => 'You\'re saving ' . round($savingsRate, 1) . '% of your income. Keep it up!',
            ];
        }

        // Confidence-based recommendation
        if ($confidenceScore['level'] === 'low' || $confidenceScore['level'] === 'very_low') {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Variable Income',
                'message' => 'Your income varies significantly. Consider building a larger emergency fund.',
            ];
        }

        // Expense ratio recommendation
        $expenseRatio = $monthlyIncome > 0 ? ($monthlyExpenses / $monthlyIncome) * 100 : 0;
        if ($expenseRatio > 90) {
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'High Expense Ratio',
                'message' => 'Your expenses consume over 90% of your income. Look for areas to cut back.',
            ];
        }

        // Scenario-specific recommendations
        if ($this->scenario === 'conservative') {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Conservative Scenario',
                'message' => 'This projection uses 90% of your average income. Good for stress-testing your finances.',
            ];
        } elseif ($this->scenario === 'optimistic') {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Optimistic Scenario',
                'message' => 'This projection uses 110% of your average income. Best-case scenario planning.',
            ];
        }

        return $recommendations;
    }

    private function formatHistoricalData(array $monthlyIncome, array $monthlyExpenses): array
    {
        $historical = [];
        $currentDate = Carbon::now();

        // Last 6 months of historical data
        for ($i = 5; $i >= 0; $i--) {
            $date = $currentDate->copy()->subMonths($i);
            $monthKey = $date->format('Y-m');
            
            $historical[] = [
                'month' => $monthKey,
                'income' => $monthlyIncome[$i] ?? 0,
                'expenses' => $monthlyExpenses[$i] ?? 0,
            ];
        }

        return $historical;
    }
}
