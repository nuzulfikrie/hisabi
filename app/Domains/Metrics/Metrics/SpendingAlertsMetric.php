<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Budget\Models\Budget;
use Carbon\Carbon;

class SpendingAlertsMetric extends Metric
{
    public function calculate(): array
    {
        $alerts = [];
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // 1. Check for spending increases >30% in any category vs previous month
        $categories = \App\Models\Category::where('categories.type', 'EXPENSES')->get();
        
        foreach ($categories as $category) {
            $currentMonthSpending = Transaction::query()
                ->expenses()
                ->whereHas('brand', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })
                ->where('created_at', '>=', $currentMonthStart)
                ->sum('amount');

            $lastMonthSpending = Transaction::query()
                ->expenses()
                ->whereHas('brand', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            if ($lastMonthSpending > 0 && $currentMonthSpending > 0) {
                $increasePercent = (($currentMonthSpending - $lastMonthSpending) / $lastMonthSpending) * 100;
                
                if ($increasePercent > 50) {
                    $alerts[] = [
                        'type' => 'spending_increase_critical',
                        'severity' => 'critical',
                        'message' => "Spending in {$category->name} increased by " . round($increasePercent) . '% vs last month',
                        'category' => $category->name,
                        'current_amount' => $currentMonthSpending,
                        'previous_amount' => $lastMonthSpending,
                        'increase_percent' => round($increasePercent, 2),
                    ];
                } elseif ($increasePercent > 30) {
                    $alerts[] = [
                        'type' => 'spending_increase_warning',
                        'severity' => 'warning',
                        'message' => "Spending in {$category->name} increased by " . round($increasePercent) . '% vs last month',
                        'category' => $category->name,
                        'current_amount' => $currentMonthSpending,
                        'previous_amount' => $lastMonthSpending,
                        'increase_percent' => round($increasePercent, 2),
                    ];
                } elseif ($increasePercent < -20) {
                    $alerts[] = [
                        'type' => 'spending_decrease_positive',
                        'severity' => 'positive',
                        'message' => "Great! Spending in {$category->name} decreased by " . round(abs($increasePercent)) . '% vs last month',
                        'category' => $category->name,
                        'current_amount' => $currentMonthSpending,
                        'previous_amount' => $lastMonthSpending,
                        'decrease_percent' => round(abs($increasePercent), 2),
                    ];
                }
            }
        }

        // 2. Budget alerts (80% and 100% thresholds)
        $activeBudgets = Budget::where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->get();

        foreach ($activeBudgets as $budget) {
            $spentPercentage = (float) $budget->total_spent_percentage;
            
            if ($spentPercentage >= 100) {
                $alerts[] = [
                    'type' => 'budget_exceeded',
                    'severity' => 'critical',
                    'message' => "Budget '{$budget->name}' exceeded by " . round($spentPercentage - 100) . '%',
                    'budget_name' => $budget->name,
                    'budget_amount' => $budget->amount,
                    'spent_amount' => $budget->total_transactions_amount,
                    'spent_percentage' => round($spentPercentage, 2),
                ];
            } elseif ($spentPercentage >= 80) {
                $alerts[] = [
                    'type' => 'budget_warning',
                    'severity' => 'warning',
                    'message' => "Budget '{$budget->name}' at " . round($spentPercentage) . '% - Approaching limit',
                    'budget_name' => $budget->name,
                    'budget_amount' => $budget->amount,
                    'spent_amount' => $budget->total_transactions_amount,
                    'spent_percentage' => round($spentPercentage, 2),
                ];
            }
        }

        // Sort alerts by severity: critical first, then warning, then positive
        usort($alerts, function ($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1, 'positive' => 2];
            return $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        });

        // Limit to 5 alerts
        $alerts = array_slice($alerts, 0, 5);

        return [
            'value' => count($alerts),
            'alerts' => $alerts,
            'has_critical' => collect($alerts)->contains('severity', 'critical'),
            'has_warning' => collect($alerts)->contains('severity', 'warning'),
            'has_positive' => collect($alerts)->contains('severity', 'positive'),
        ];
    }
}
