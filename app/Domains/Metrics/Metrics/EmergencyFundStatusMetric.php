<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class EmergencyFundStatusMetric extends Metric
{
    public function calculate(): array
    {
        // Get total savings
        $savingsQuery = Transaction::query()->savings();
        if ($this->hasDateRange()) {
            $savingsQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }
        $totalSavings = $savingsQuery->sum('amount');

        // Calculate average monthly expenses (last 6 months)
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

        // Calculate months of expenses covered
        $monthsCovered = $avgMonthlyExpenses > 0 
            ? $totalSavings / $avgMonthlyExpenses 
            : 0;

        // Targets
        $target3Month = 3;
        $target6Month = 6;

        // Gap calculations
        $gapTo3Month = max(0, ($avgMonthlyExpenses * $target3Month) - $totalSavings);
        $gapTo6Month = max(0, ($avgMonthlyExpenses * $target6Month) - $totalSavings);

        // Status based on months covered
        $status = 'red';
        if ($monthsCovered >= 6) {
            $status = 'green';
        } elseif ($monthsCovered >= 3) {
            $status = 'yellow';
        }

        // Previous period comparison
        $previousMonthsCovered = 0;
        $previousRange = $this->getPreviousRange();
        if ($previousRange) {
            $prevSavings = Transaction::query()
                ->savings()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->sum('amount');
            $previousMonthsCovered = $avgMonthlyExpenses > 0 
                ? $prevSavings / $avgMonthlyExpenses 
                : 0;
        }

        return [
            'value' => round($monthsCovered, 2),
            'previous' => round($previousMonthsCovered, 2),
            'total_savings' => $totalSavings,
            'avg_monthly_expenses' => round($avgMonthlyExpenses, 2),
            'target_3_month' => $target3Month,
            'target_6_month' => $target6Month,
            'gap_to_3_month' => round($gapTo3Month, 2),
            'gap_to_6_month' => round($gapTo6Month, 2),
            'status' => $status,
        ];
    }
}
