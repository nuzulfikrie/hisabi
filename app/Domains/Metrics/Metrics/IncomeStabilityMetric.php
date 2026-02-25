<?php

namespace App\Domains\Metrics\Metrics;

use App\Domains\Metrics\Metric;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;

class IncomeStabilityMetric extends Metric
{
    public function calculate(): array
    {
        // Get income for last 12 months
        $twelveMonthsAgo = Carbon::now()->subMonths(12);
        $monthlyIncome = Transaction::query()
            ->income()
            ->where('created_at', '>=', $twelveMonthsAgo)
            ->selectRaw('SUM(amount) as total, ' . $this->getDateFormat('%Y-%m') . ' as month')
            ->groupBy('month')
            ->pluck('total')
            ->toArray();

        $monthCount = count($monthlyIncome);
        
        if ($monthCount < 2) {
            return [
                'value' => 0,
                'stability_score' => 'Unknown',
                'coefficient_of_variation' => 0,
                'average_income' => 0,
                'min_income' => 0,
                'max_income' => 0,
                'standard_deviation' => 0,
                'months_of_data' => $monthCount,
                'status' => 'unknown',
            ];
        }

        // Calculate statistics
        $avgIncome = array_sum($monthlyIncome) / count($monthlyIncome);
        $minIncome = min($monthlyIncome);
        $maxIncome = max($monthlyIncome);

        // Calculate standard deviation
        $variance = 0;
        foreach ($monthlyIncome as $income) {
            $variance += pow($income - $avgIncome, 2);
        }
        $variance = $variance / count($monthlyIncome);
        $stdDev = sqrt($variance);

        // Coefficient of variation (CV) = (Std Dev / Mean) * 100
        $cv = $avgIncome > 0 ? ($stdDev / $avgIncome) * 100 : 0;

        // Determine stability based on CV
        // CV < 10%: Stable, 10-25%: Moderate, 25-50%: Variable, >50%: Highly Variable
        if ($cv < 10) {
            $stabilityScore = 'Stable';
            $status = 'green';
        } elseif ($cv < 25) {
            $stabilityScore = 'Moderate';
            $status = 'yellow';
        } elseif ($cv < 50) {
            $stabilityScore = 'Variable';
            $status = 'orange';
        } else {
            $stabilityScore = 'Highly Variable';
            $status = 'red';
        }

        // Calculate trend (last 3 months vs previous 3 months)
        $recentMonths = array_slice($monthlyIncome, -3);
        $previousMonths = array_slice($monthlyIncome, -6, 3);
        
        $recentAvg = count($recentMonths) > 0 ? array_sum($recentMonths) / count($recentMonths) : 0;
        $previousAvg = count($previousMonths) > 0 ? array_sum($previousMonths) / count($previousMonths) : 0;
        
        $trend = 'stable';
        if ($previousAvg > 0) {
            $trendPercent = (($recentAvg - $previousAvg) / $previousAvg) * 100;
            if ($trendPercent > 10) {
                $trend = 'increasing';
            } elseif ($trendPercent < -10) {
                $trend = 'decreasing';
            }
        }

        return [
            'value' => round($cv, 2),
            'stability_score' => $stabilityScore,
            'coefficient_of_variation' => round($cv, 2),
            'average_income' => round($avgIncome, 2),
            'min_income' => round($minIncome, 2),
            'max_income' => round($maxIncome, 2),
            'standard_deviation' => round($stdDev, 2),
            'months_of_data' => $monthCount,
            'status' => $status,
            'trend' => $trend,
            'monthly_income' => $monthlyIncome,
        ];
    }
}
