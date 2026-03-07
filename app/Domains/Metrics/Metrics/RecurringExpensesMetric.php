<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringExpensesMetric extends Metric
{
    public function calculate(): array
    {
        // Look for transactions with similar amounts at the same brand
        // grouped by month, to detect recurring patterns
        
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        
        // Find brands with multiple transactions in different months
        $brandPatterns = Transaction::query()
            ->expenses()
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw('
                brand_id,
                COUNT(*) as transaction_count,
                COUNT(DISTINCT ' . $this->getDateFormat('%Y-%m') . ') as month_count,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                MAX(created_at) as last_transaction_date
            ')
            ->groupBy('brand_id')
            ->havingRaw('month_count >= 2') // At least 2 different months
            ->get();

        $recurringExpenses = [];
        $totalMonthlyRecurring = 0;

        foreach ($brandPatterns as $pattern) {
            $brand = \App\Domains\Brand\Models\Brand::with('category')->find($pattern->brand_id);
            
            if (!$brand) {
                continue;
            }

            // Determine frequency based on transaction pattern
            $frequency = $this->determineFrequency($pattern->transaction_count, $pattern->month_count);
            
            // Check if potentially unused (>60 days since last transaction)
            $lastTransaction = Carbon::parse($pattern->last_transaction_date);
            $daysSinceLastTransaction = $lastTransaction->diffInDays(Carbon::now());
            $potentiallyUnused = $daysSinceLastTransaction > 60;

            // Amount variation
            $amountVariation = $pattern->avg_amount > 0 
                ? (($pattern->max_amount - $pattern->min_amount) / $pattern->avg_amount) * 100 
                : 0;

            $expense = [
                'brand_id' => $pattern->brand_id,
                'brand_name' => $brand->name,
                'category_name' => $brand->category?->name ?? 'Uncategorized',
                'avg_amount' => round($pattern->avg_amount, 2),
                'min_amount' => round($pattern->min_amount, 2),
                'max_amount' => round($pattern->max_amount, 2),
                'transaction_count' => $pattern->transaction_count,
                'month_count' => $pattern->month_count,
                'frequency' => $frequency,
                'last_transaction_date' => $lastTransaction->format('Y-m-d'),
                'days_since_last' => $daysSinceLastTransaction,
                'potentially_unused' => $potentiallyUnused,
                'amount_variation_percent' => round($amountVariation, 2),
            ];

            $recurringExpenses[] = $expense;

            // Estimate monthly cost
            $monthlyAmount = $this->estimateMonthlyAmount($pattern->avg_amount, $frequency);
            $totalMonthlyRecurring += $monthlyAmount;
        }

        // Sort by monthly impact (highest first)
        usort($recurringExpenses, function ($a, $b) {
            return $b['avg_amount'] <=> $a['avg_amount'];
        });

        // Group by frequency
        $groupedByFrequency = [
            'monthly' => array_filter($recurringExpenses, fn($e) => $e['frequency'] === 'monthly'),
            'quarterly' => array_filter($recurringExpenses, fn($e) => $e['frequency'] === 'quarterly'),
            'annual' => array_filter($recurringExpenses, fn($e) => $e['frequency'] === 'annual'),
            'irregular' => array_filter($recurringExpenses, fn($e) => $e['frequency'] === 'irregular'),
        ];

        $potentiallyUnusedCount = count(array_filter($recurringExpenses, fn($e) => $e['potentially_unused']));

        return [
            'value' => count($recurringExpenses),
            'total_monthly_recurring' => round($totalMonthlyRecurring, 2),
            'expenses' => $recurringExpenses,
            'grouped_by_frequency' => $groupedByFrequency,
            'potentially_unused_count' => $potentiallyUnusedCount,
            'monthly_count' => count($groupedByFrequency['monthly']),
            'quarterly_count' => count($groupedByFrequency['quarterly']),
            'annual_count' => count($groupedByFrequency['annual']),
        ];
    }

    private function determineFrequency(int $transactionCount, int $monthCount): string
    {
        $ratio = $monthCount > 0 ? $transactionCount / $monthCount : 0;
        
        if ($ratio >= 0.8 && $ratio <= 1.2) {
            return 'monthly';
        } elseif ($ratio >= 0.2 && $ratio <= 0.4) {
            return 'quarterly';
        } elseif ($ratio >= 0.05 && $ratio <= 0.15) {
            return 'annual';
        }
        
        return 'irregular';
    }

    private function estimateMonthlyAmount(float $avgAmount, string $frequency): float
    {
        return match ($frequency) {
            'monthly' => $avgAmount,
            'quarterly' => $avgAmount / 3,
            'annual' => $avgAmount / 12,
            default => $avgAmount / 2, // Conservative estimate for irregular
        };
    }
}
