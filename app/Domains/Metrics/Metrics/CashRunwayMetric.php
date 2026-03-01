<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class CashRunwayMetric extends Metric
{
    public function calculate(): array
    {
        // Get available cash (savings that are liquid/cash)
        $cashQuery = Transaction::query()->savings();
        if ($this->hasDateRange()) {
            $cashQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }
        $availableCash = $cashQuery->sum('amount');

        // Calculate monthly burn rate (average monthly expenses)
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $monthlyExpenses = Transaction::query()
            ->expenses()
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('SUM(amount) as total, ' . $this->getDateFormat('%Y-%m') . ' as month')
            ->groupBy('month')
            ->pluck('total')
            ->toArray();

        $monthCount = count($monthlyExpenses);
        $avgMonthlyExpenses = $monthCount > 0 
            ? array_sum($monthlyExpenses) / $monthCount 
            : 0;

        // Calculate runway
        $runwayMonths = $avgMonthlyExpenses > 0 
            ? $availableCash / $avgMonthlyExpenses 
            : 0;

        // Daily burn rate
        $avgDailyExpenses = $monthCount > 0 
            ? $avgMonthlyExpenses / 30 
            : 0;

        // Status based on runway
        $status = 'red';
        if ($runwayMonths >= 6) {
            $status = 'green';
        } elseif ($runwayMonths >= 3) {
            $status = 'yellow';
        }

        // Warning if runway < 3 months
        $needsAttention = $runwayMonths < 3;

        // Previous period comparison
        $previousRunway = 0;
        $previousRange = $this->getPreviousRange();
        if ($previousRange) {
            $prevCash = Transaction::query()
                ->savings()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->sum('amount');
            $previousRunway = $avgMonthlyExpenses > 0 
                ? $prevCash / $avgMonthlyExpenses 
                : 0;
        }

        return [
            'value' => round($runwayMonths, 2),
            'previous' => round($previousRunway, 2),
            'available_cash' => $availableCash,
            'monthly_burn_rate' => round($avgMonthlyExpenses, 2),
            'daily_burn_rate' => round($avgDailyExpenses, 2),
            'runway_months' => round($runwayMonths, 2),
            'runway_days' => round($runwayMonths * 30),
            'status' => $status,
            'needs_attention' => $needsAttention,
            'months_of_data' => $monthCount,
        ];
    }
}
