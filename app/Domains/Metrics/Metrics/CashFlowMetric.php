<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;

class CashFlowMetric extends Metric
{
    public function calculate(): array
    {
        $incomeQuery = Transaction::query()->income();
        $expensesQuery = Transaction::query()->expenses();

        if ($this->hasDateRange()) {
            $incomeQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
            $expensesQuery->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }

        $income = $incomeQuery->sum('amount');
        $expenses = $expensesQuery->sum('amount');
        $cashFlow = $income - $expenses;

        $previousIncome = 0;
        $previousExpenses = 0;
        $previousCashFlow = 0;

        $previousRange = $this->getPreviousRange();
        if ($previousRange) {
            $previousIncome = Transaction::query()
                ->income()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->sum('amount');

            $previousExpenses = Transaction::query()
                ->expenses()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->sum('amount');

            $previousCashFlow = $previousIncome - $previousExpenses;
        }

        return [
            'value' => $cashFlow,
            'previous' => $previousCashFlow,
            'income' => $income,
            'expenses' => $expenses,
            'previousIncome' => $previousIncome,
            'previousExpenses' => $previousExpenses,
        ];
    }
}
