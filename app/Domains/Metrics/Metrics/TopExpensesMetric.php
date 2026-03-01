<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;

class TopExpensesMetric extends Metric
{
    public function calculate(): array
    {
        $query = Transaction::query()
            ->expenses()
            ->with(['brand', 'brand.category'])
            ->orderBy('amount', 'desc');

        if ($this->hasDateRange()) {
            $query->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);
        }

        $topExpenses = $query->limit(10)->get()->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'brand_name' => $transaction->brand?->name ?? 'Unknown',
                'category_name' => $transaction->brand?->category?->name ?? 'Uncategorized',
                'category_type' => $transaction->brand?->category?->type ?? null,
                'date' => $transaction->created_at->format('Y-m-d'),
                'date_formatted' => $transaction->created_at->format('M d, Y'),
            ];
        });

        $totalAmount = $topExpenses->sum('amount');

        // Get previous period top expense for comparison
        $previousTopAmount = 0;
        $previousRange = $this->getPreviousRange();
        if ($previousRange) {
            $previousTopAmount = Transaction::query()
                ->expenses()
                ->whereBetween('created_at', [$previousRange['start'], $previousRange['end']])
                ->max('amount') ?? 0;
        }

        return [
            'value' => $topExpenses->count(),
            'expenses' => $topExpenses,
            'total_amount' => $topExpenses->sum('amount'),
            'average_amount' => $topExpenses->count() > 0 ? round($topExpenses->avg('amount'), 2) : 0,
            'highest_amount' => $topExpenses->first()['amount'] ?? 0,
            'previous_highest' => $previousTopAmount,
        ];
    }
}
