<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Category;

class BudgetAllocationMetric extends Metric
{
    public function calculate(): array
    {
        // Get totals by category type
        $incomeQuery = Transaction::query()->income();
        $expensesQuery = Transaction::query()->expenses();

        if ($this->hasDateRange()) {
            $incomeQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
            $expensesQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }

        $totalIncome = $incomeQuery->sum('amount');
        $totalExpenses = $expensesQuery->sum('amount');

        // Get category breakdown for expenses (to classify needs vs wants)
        $categories = Category::where('type', Category::EXPENSES)
            ->with(['brands.transactions'])
            ->get();

        $needsAmount = 0;
        $wantsAmount = 0;
        $uncategorizedAmount = 0;

        foreach ($categories as $category) {
            $categoryTotal = 0;
            
            foreach ($category->brands as $brand) {
                $query = $brand->transactions()->expenses();
                if ($this->hasDateRange()) {
                    $query->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
                }
                $categoryTotal += $query->sum('amount');
            }

            // Classify based on category name/purpose
            // This is a simplified classification - in practice might need a flag on categories
            $categoryName = strtolower($category->name);
            $needsKeywords = ['rent', 'mortgage', 'utilities', 'groceries', 'food', 'transport', 'health', 'insurance', 'bills', 'loan'];
            $wantsKeywords = ['entertainment', 'dining', 'shopping', 'travel', 'hobbies', 'subscriptions', 'luxury'];
            
            $isNeed = false;
            $isWant = false;
            
            foreach ($needsKeywords as $keyword) {
                if (str_contains($categoryName, $keyword)) {
                    $isNeed = true;
                    break;
                }
            }
            
            if (!$isNeed) {
                foreach ($wantsKeywords as $keyword) {
                    if (str_contains($categoryName, $keyword)) {
                        $isWant = true;
                        break;
                    }
                }
            }

            if ($isNeed) {
                $needsAmount += $categoryTotal;
            } elseif ($isWant) {
                $wantsAmount += $categoryTotal;
            } else {
                $uncategorizedAmount += $categoryTotal;
            }
        }

        // Add uncategorized to needs (conservative approach)
        $needsAmount += $uncategorizedAmount;

        // Get savings
        $savingsQuery = Transaction::query()->savings();
        $investmentQuery = Transaction::query()->investment();
        
        if ($this->hasDateRange()) {
            $savingsQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
            $investmentQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }
        
        $totalSavings = $savingsQuery->sum('amount') + $investmentQuery->sum('amount');

        // Calculate percentages based on 50/30/20 rule targets
        // 50% Needs, 30% Wants, 20% Savings
        $targetNeedsPercent = 50;
        $targetWantsPercent = 30;
        $targetSavingsPercent = 20;

        // Calculate actual percentages
        $actualNeedsPercent = $totalIncome > 0 ? ($needsAmount / $totalIncome) * 100 : 0;
        $actualWantsPercent = $totalIncome > 0 ? ($wantsAmount / $totalIncome) * 100 : 0;
        $actualSavingsPercent = $totalIncome > 0 ? ($totalSavings / $totalIncome) * 100 : 0;

        // Calculate variance from target
        $needsVariance = $actualNeedsPercent - $targetNeedsPercent;
        $wantsVariance = $actualWantsPercent - $targetWantsPercent;
        $savingsVariance = $actualSavingsPercent - $targetSavingsPercent;

        // Generate recommendations
        $recommendations = [];
        
        if ($actualNeedsPercent > 55) {
            $recommendations[] = 'Consider reducing essential expenses - currently at ' . round($actualNeedsPercent) . '% (target: 50%)';
        }
        
        if ($actualWantsPercent > 35) {
            $recommendations[] = 'Discretionary spending is high at ' . round($actualWantsPercent) . '% (target: 30%)';
        }
        
        if ($actualSavingsPercent < 15) {
            $recommendations[] = 'Try to increase savings to at least 20% of income (currently ' . round($actualSavingsPercent) . '%)';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Your budget allocation looks good! Keep maintaining this balance.';
        }

        return [
            'value' => round($actualSavingsPercent, 2), // Primary value is savings rate
            'total_income' => $totalIncome,
            'needs' => [
                'amount' => round($needsAmount, 2),
                'actual_percent' => round($actualNeedsPercent, 2),
                'target_percent' => $targetNeedsPercent,
                'variance' => round($needsVariance, 2),
            ],
            'wants' => [
                'amount' => round($wantsAmount, 2),
                'actual_percent' => round($actualWantsPercent, 2),
                'target_percent' => $targetWantsPercent,
                'variance' => round($wantsVariance, 2),
            ],
            'savings' => [
                'amount' => round($totalSavings, 2),
                'actual_percent' => round($actualSavingsPercent, 2),
                'target_percent' => $targetSavingsPercent,
                'variance' => round($savingsVariance, 2),
            ],
            'recommendations' => $recommendations,
        ];
    }
}
