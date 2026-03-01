<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Budget\Models\Budget;
use Carbon\Carbon;

class FinancialHealthScoreMetric extends Metric
{
    public function calculate(): array
    {
        $scores = [];
        $weights = [
            'emergency_fund' => 25,
            'cash_flow' => 25,
            'savings_rate' => 25,
            'budget_compliance' => 25,
        ];

        // 1. Emergency Fund Score (0-100) - based on months of expenses covered
        $totalSavings = Transaction::query()->savings()->sum('amount');
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $monthlyExpenses = Transaction::query()
            ->expenses()
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('SUM(amount) as total, ' . $this->getDateFormat('%Y-%m') . ' as month')
            ->groupBy('month')
            ->pluck('total')
            ->toArray();
        $avgMonthlyExpenses = count($monthlyExpenses) > 0 
            ? array_sum($monthlyExpenses) / count($monthlyExpenses) 
            : 0;
        $monthsCovered = $avgMonthlyExpenses > 0 ? $totalSavings / $avgMonthlyExpenses : 0;
        
        // Score: <1 month = 0, 1-3 months = 0-50, 3-6 months = 50-75, 6+ months = 75-100
        if ($monthsCovered >= 6) {
            $scores['emergency_fund'] = min(100, 75 + (($monthsCovered - 6) * 5));
        } elseif ($monthsCovered >= 3) {
            $scores['emergency_fund'] = 50 + (($monthsCovered - 3) * 8.33);
        } elseif ($monthsCovered >= 1) {
            $scores['emergency_fund'] = ($monthsCovered - 1) * 25;
        } else {
            $scores['emergency_fund'] = 0;
        }

        // 2. Cash Flow Score (0-100) - based on positive cash flow
        $income = Transaction::query()->income()->sum('amount');
        $expenses = Transaction::query()->expenses()->sum('amount');
        $cashFlow = $income - $expenses;
        
        // Score: negative = 0-30, break-even = 50, 20%+ surplus = 100
        if ($cashFlow > 0 && $income > 0) {
            $surplusRate = ($cashFlow / $income) * 100;
            $scores['cash_flow'] = min(100, 50 + ($surplusRate * 2.5));
        } elseif ($cashFlow == 0) {
            $scores['cash_flow'] = 50;
        } else {
            $deficitRate = $income > 0 ? (abs($cashFlow) / $income) * 100 : 100;
            $scores['cash_flow'] = max(0, 30 - $deficitRate);
        }

        // 3. Savings Rate Score (0-100)
        $savingsRate = $income > 0 ? (($income - $expenses) / $income) * 100 : 0;
        // Score: <10% = 0-40, 10-20% = 40-80, >20% = 80-100
        if ($savingsRate >= 20) {
            $scores['savings_rate'] = min(100, 80 + (($savingsRate - 20) * 1));
        } elseif ($savingsRate >= 10) {
            $scores['savings_rate'] = 40 + (($savingsRate - 10) * 4);
        } else {
            $scores['savings_rate'] = max(0, $savingsRate * 4);
        }

        // 4. Budget Compliance Score (0-100) - based on budget adherence
        $activeBudgets = Budget::where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->get();
        
        if ($activeBudgets->count() > 0) {
            $totalCompliance = 0;
            foreach ($activeBudgets as $budget) {
                $spentPercentage = (float) $budget->total_spent_percentage;
                // Score: under budget = 100, at 100% = 80, over = drops faster
                if ($spentPercentage <= 80) {
                    $totalCompliance += 100;
                } elseif ($spentPercentage <= 100) {
                    $totalCompliance += 100 - (($spentPercentage - 80) * 1);
                } else {
                    $totalCompliance += max(0, 80 - (($spentPercentage - 100) * 2));
                }
            }
            $scores['budget_compliance'] = $totalCompliance / $activeBudgets->count();
        } else {
            $scores['budget_compliance'] = 50; // Neutral if no budgets
        }

        // Calculate weighted total score
        $totalScore = 0;
        foreach ($scores as $component => $score) {
            $totalScore += $score * ($weights[$component] / 100);
        }

        $totalScore = round($totalScore);

        // Determine status text
        $status = 'Needs Attention';
        if ($totalScore >= 90) {
            $status = 'Excellent';
        } elseif ($totalScore >= 70) {
            $status = 'Good';
        } elseif ($totalScore >= 50) {
            $status = 'Fair';
        }

        return [
            'value' => $totalScore,
            'status' => $status,
            'components' => [
                'emergency_fund' => [
                    'score' => round($scores['emergency_fund']),
                    'weight' => $weights['emergency_fund'],
                    'months_covered' => round($monthsCovered, 2),
                ],
                'cash_flow' => [
                    'score' => round($scores['cash_flow']),
                    'weight' => $weights['cash_flow'],
                    'cash_flow' => round($cashFlow, 2),
                ],
                'savings_rate' => [
                    'score' => round($scores['savings_rate']),
                    'weight' => $weights['savings_rate'],
                    'savings_rate' => round($savingsRate, 2),
                ],
                'budget_compliance' => [
                    'score' => round($scores['budget_compliance']),
                    'weight' => $weights['budget_compliance'],
                    'active_budgets' => $activeBudgets->count(),
                ],
            ],
        ];
    }
}
