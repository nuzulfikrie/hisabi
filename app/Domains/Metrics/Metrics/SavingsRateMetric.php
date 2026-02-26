<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;

class SavingsRateMetric extends Metric
{
    public function calculate(): array
    {
        $incomeQuery = Transaction::query()->income();
        $expensesQuery = Transaction::query()->expenses();
        $savingsQuery = Transaction::query()->savings();

        if ($this->hasDateRange()) {
            $incomeQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
            $expensesQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
            $savingsQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }

        $income = $incomeQuery->sum('amount');
        $expenses = $expensesQuery->sum('amount');
        $savings = $savingsQuery->sum('amount');

        // Savings rate = (Income - Expenses) / Income * 100
        // Or directly: Savings / Income * 100
        $savingsRate = $income > 0 ? (($income - $expenses) / $income) * 100 : 0;
        $directSavingsRate = $income > 0 ? ($savings / $income) * 100 : 0;

        $previousRate = 0;
        $previousRange = $this->getPreviousRange();
        if ($previousRange) {
            $prevIncome = Transaction::query()
                ->income()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->sum('amount');
            $prevExpenses = Transaction::query()
                ->expenses()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->sum('amount');
            $previousRate = $prevIncome > 0 ? (($prevIncome - $prevExpenses) / $prevIncome) * 100 : 0;
        }

        // Target is 20%
        $targetRate = 20.0;
        $gapToTarget = max(0, $targetRate - $savingsRate);

        // Color coding based on savings rate
        $status = 'red';
        if ($savingsRate >= 20) {
            $status = 'green';
        } elseif ($savingsRate >= 10) {
            $status = 'yellow';
        }

        return [
            'value' => round($savingsRate, 2),
            'previous' => round($previousRate, 2),
            'income' => $income,
            'expenses' => $expenses,
            'savings' => $savings,
            'target_rate' => $targetRate,
            'gap_to_target' => round($gapToTarget, 2),
            'status' => $status,
            'direct_rate' => round($directSavingsRate, 2),
        ];
    }
}
