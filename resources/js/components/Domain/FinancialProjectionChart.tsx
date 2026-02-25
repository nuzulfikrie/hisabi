import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import {
    Chart,
    LineElement,
    Tooltip,
    LineController,
    CategoryScale,
    LinearScale,
    PointElement,
    Filler,
    Legend,
} from 'chart.js';
import AnnotationPlugin from 'chartjs-plugin-annotation';

import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import LoadingView from "@/components/Global/LoadingView";
import { formatNumber, getAppCurrency } from '@/Utils';
import { useInView } from '@/hooks/useInView';

Chart.register(LineElement, Tooltip, LineController, CategoryScale, LinearScale, PointElement, Filler, Legend, AnnotationPlugin);

type Scenario = 'conservative' | 'realistic' | 'optimistic';

interface ProjectionPoint {
    month: string;
    value: number;
    lower_bound: number;
    upper_bound: number;
}

interface SavingsPoint {
    month: string;
    value: number;
}

interface HistoricalPoint {
    month: string;
    income: number;
    expenses: number;
}

interface ConfidenceScore {
    score: number;
    level: string;
    description: string;
}

interface Recommendation {
    type: 'success' | 'warning' | 'caution' | 'info';
    title: string;
    message: string;
}

interface FinancialProjectionData {
    scenario: Scenario;
    projection_months: number;
    current_net_worth: number;
    monthly_averages: {
        income: number;
        expenses: number;
        savings: number;
    };
    scenario_adjustments: {
        income_multiplier: number;
        adjusted_income: number;
    };
    projected_net_worth: ProjectionPoint[];
    projected_savings: SavingsPoint[];
    projected_expenses: SavingsPoint[];
    confidence_score: ConfidenceScore;
    recommendations: Recommendation[];
    historical_data: HistoricalPoint[];
}

interface FinancialProjectionChartProps {
    dateRange: DateRange | undefined;
}

const fetchFinancialProjection = async (
    dateRange: DateRange | undefined,
    scenario: Scenario,
    months: number
): Promise<FinancialProjectionData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
        scenario: scenario,
        months: months.toString(),
    });
    
    const response = await fetch(`/api/v1/metrics/financial-projection?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

const getScenarioColor = (scenario: Scenario): string => {
    switch (scenario) {
        case 'conservative':
            return '#f59e0b'; // amber-500
        case 'optimistic':
            return '#10b981'; // emerald-500
        case 'realistic':
        default:
            return '#0ea5e9'; // sky-500
    }
};

const getScenarioLabel = (scenario: Scenario): string => {
    switch (scenario) {
        case 'conservative':
            return 'Conservative (-10%)';
        case 'optimistic':
            return 'Optimistic (+10%)';
        case 'realistic':
        default:
            return 'Realistic (Average)';
    }
};

const getRecommendationIcon = (type: string): string => {
    switch (type) {
        case 'success':
            return '✓';
        case 'warning':
            return '⚠';
        case 'caution':
            return '⚡';
        case 'info':
        default:
            return 'ℹ';
    }
};

const getRecommendationClass = (type: string): string => {
    switch (type) {
        case 'success':
            return 'bg-green-50 border-green-200 text-green-800';
        case 'warning':
            return 'bg-red-50 border-red-200 text-red-800';
        case 'caution':
            return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'info':
        default:
            return 'bg-blue-50 border-blue-200 text-blue-800';
    }
};

const getConfidenceBadgeClass = (level: string): string => {
    switch (level) {
        case 'high':
            return 'bg-green-100 text-green-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-orange-100 text-orange-800';
        case 'very_low':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

export default function FinancialProjectionChart({ dateRange }: FinancialProjectionChartProps) {
    const [data, setData] = useState<FinancialProjectionData | null>(null);
    const [scenario, setScenario] = useState<Scenario>('realistic');
    const [projectionMonths, setProjectionMonths] = useState<number>(12);
    const [chartInstance, setChartInstance] = useState<Chart | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView) return;

        fetchFinancialProjection(dateRange, scenario, projectionMonths)
            .then(setData)
            .catch(console.error);
    }, [dateRange, scenario, projectionMonths, isInView]);

    useEffect(() => {
        if (!data) return;

        if (chartInstance) {
            chartInstance.destroy();
        }

        const ctx = document.getElementById('financial-projection-chart') as HTMLCanvasElement;
        if (!ctx) return;

        const context = ctx.getContext('2d');
        if (!context) return;

        // Prepare labels (historical + projected months)
        const historicalLabels = data.historical_data.map(d => d.month);
        const projectedLabels = data.projected_net_worth.map(d => d.month);
        const allLabels = [...historicalLabels, ...projectedLabels];

        // Historical net worth calculation (cumulative)
        const historicalNetWorth: number[] = [];
        let runningTotal = 0;
        // We need to calculate from the beginning of historical data
        for (let i = 0; i < data.historical_data.length; i++) {
            const month = data.historical_data[i];
            runningTotal += (month.income - month.expenses);
            historicalNetWorth.push(runningTotal);
        }

        // Projected net worth values
        const projectedValues = data.projected_net_worth.map(d => d.value);
        const projectedLower = data.projected_net_worth.map(d => d.lower_bound);
        const projectedUpper = data.projected_net_worth.map(d => d.upper_bound);

        // Create gradient for projected area
        const gradient = context.createLinearGradient(0, 0, 0, 300);
        const scenarioColor = getScenarioColor(scenario);
        gradient.addColorStop(0, scenarioColor + '40'); // 25% opacity
        gradient.addColorStop(1, scenarioColor + '10'); // 6% opacity

        // Split point index (where historical ends and projection begins)
        const splitIndex = historicalNetWorth.length - 1;

        const chart = new Chart(context, {
            type: 'line',
            data: {
                labels: allLabels,
                datasets: [
                    // Confidence band (upper)
                    {
                        label: 'Upper Bound',
                        data: [...Array(historicalNetWorth.length).fill(null), ...projectedUpper],
                        borderColor: 'transparent',
                        backgroundColor: 'transparent',
                        pointRadius: 0,
                        fill: false,
                    },
                    // Confidence band (lower) - fill to upper
                    {
                        label: 'Confidence Range',
                        data: [...Array(historicalNetWorth.length).fill(null), ...projectedLower],
                        borderColor: 'transparent',
                        backgroundColor: scenarioColor + '20',
                        pointRadius: 0,
                        fill: '-1',
                    },
                    // Projected net worth line
                    {
                        label: `Projected (${getScenarioLabel(scenario)})`,
                        data: [...Array(historicalNetWorth.length).fill(null), ...projectedValues],
                        borderColor: scenarioColor,
                        backgroundColor: gradient,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 4,
                        pointBackgroundColor: scenarioColor,
                        fill: false,
                    },
                    // Historical net worth line
                    {
                        label: 'Historical',
                        data: [...historicalNetWorth, null],
                        borderColor: '#6366f1', // indigo-500
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#6366f1',
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            filter: (item) => item.text !== 'Upper Bound',
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1f2937',
                        bodyColor: '#1f2937',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed.y;
                                if (value === null) return '';
                                return `${context.dataset.label}: ${getAppCurrency()} ${formatNumber(value)}`;
                            },
                        },
                    },
                    annotation: {
                        annotations: {
                            splitLine: {
                                type: 'line',
                                xMin: splitIndex,
                                xMax: splitIndex,
                                borderColor: '#9ca3af',
                                borderWidth: 2,
                                borderDash: [2, 2],
                                label: {
                                    display: true,
                                    content: 'Today',
                                    position: 'start',
                                    backgroundColor: '#9ca3af',
                                    color: '#fff',
                                    font: { size: 10 },
                                },
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                        },
                    },
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: '#f3f4f6',
                        },
                        ticks: {
                            callback: (value) => `${getAppCurrency()} ${formatNumber(value as number, '(0a)')}`,
                        },
                    },
                },
            },
        });

        setChartInstance(chart);

        return () => {
            chart.destroy();
        };
    }, [data]);

    if (!data) {
        return (
            <div ref={ref}>
                <Card className="relative h-[400px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    const finalProjectedValue = data.projected_net_worth[data.projected_net_worth.length - 1]?.value ?? 0;
    const projectedChange = finalProjectedValue - data.current_net_worth;
    const projectedChangePercent = data.current_net_worth !== 0 
        ? (projectedChange / Math.abs(data.current_net_worth)) * 100 
        : 0;

    return (
        <Card className="relative p-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h3 className="text-lg font-semibold text-gray-800">Financial Forecast</h3>
                    <p className="text-sm text-gray-500">
                        Projected net worth over {projectionMonths} months
                    </p>
                </div>
                
                {/* Controls */}
                <div className="flex flex-wrap gap-2">
                    {/* Scenario Toggle */}
                    <div className="flex rounded-lg bg-gray-100 p-1">
                        {(['conservative', 'realistic', 'optimistic'] as Scenario[]).map((s) => (
                            <button
                                key={s}
                                onClick={() => setScenario(s)}
                                className={`px-3 py-1.5 text-xs font-medium rounded-md transition-all ${
                                    scenario === s
                                        ? 'bg-white text-gray-800 shadow-sm'
                                        : 'text-gray-600 hover:text-gray-800'
                                }`}
                            >
                                {s.charAt(0).toUpperCase() + s.slice(1)}
                            </button>
                        ))}
                    </div>

                    {/* Months Toggle */}
                    <div className="flex rounded-lg bg-gray-100 p-1">
                        {[6, 12].map((months) => (
                            <button
                                key={months}
                                onClick={() => setProjectionMonths(months)}
                                className={`px-3 py-1.5 text-xs font-medium rounded-md transition-all ${
                                    projectionMonths === months
                                        ? 'bg-white text-gray-800 shadow-sm'
                                        : 'text-gray-600 hover:text-gray-800'
                                }`}
                            >
                                {months}M
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Summary Stats */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 mb-1">Current Net Worth</p>
                    <p className="text-lg font-semibold text-gray-800">
                        {getAppCurrency()} {formatNumber(data.current_net_worth)}
                    </p>
                </div>
                <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 mb-1">Projected ({projectionMonths}M)</p>
                    <p className={`text-lg font-semibold ${projectedChange >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {getAppCurrency()} {formatNumber(finalProjectedValue)}
                    </p>
                </div>
                <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 mb-1">Expected Change</p>
                    <p className={`text-lg font-semibold ${projectedChange >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {projectedChange >= 0 ? '+' : ''}{formatNumber(projectedChangePercent, '(0.0)')}%
                    </p>
                </div>
                <div className="bg-gray-50 rounded-lg p-3">
                    <p className="text-xs text-gray-500 mb-1">Confidence</p>
                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${getConfidenceBadgeClass(data.confidence_score.level)}`}>
                        {data.confidence_score.score.toFixed(0)}%
                    </span>
                </div>
            </div>

            {/* Chart */}
            <div className="h-[300px] mb-6">
                <canvas id="financial-projection-chart" />
            </div>

            {/* Monthly Averages */}
            <div className="grid grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                <div className="text-center">
                    <p className="text-xs text-gray-500 mb-1">Avg. Monthly Income</p>
                    <p className="text-sm font-semibold text-green-600">
                        {getAppCurrency()} {formatNumber(data.monthly_averages.income)}
                    </p>
                </div>
                <div className="text-center">
                    <p className="text-xs text-gray-500 mb-1">Avg. Monthly Expenses</p>
                    <p className="text-sm font-semibold text-red-600">
                        {getAppCurrency()} {formatNumber(data.monthly_averages.expenses)}
                    </p>
                </div>
                <div className="text-center">
                    <p className="text-xs text-gray-500 mb-1">Avg. Monthly Savings</p>
                    <p className={`text-sm font-semibold ${data.monthly_averages.savings >= 0 ? 'text-blue-600' : 'text-red-600'}`}>
                        {getAppCurrency()} {formatNumber(data.monthly_averages.savings)}
                    </p>
                </div>
            </div>

            {/* Recommendations */}
            {data.recommendations.length > 0 && (
                <div className="space-y-2">
                    <h4 className="text-sm font-medium text-gray-700">Recommendations</h4>
                    <div className="space-y-2">
                        {data.recommendations.map((rec, index) => (
                            <div
                                key={index}
                                className={`p-3 rounded-lg border text-sm ${getRecommendationClass(rec.type)}`}
                            >
                                <div className="flex items-start gap-2">
                                    <span className="font-bold">{getRecommendationIcon(rec.type)}</span>
                                    <div>
                                        <p className="font-medium">{rec.title}</p>
                                        <p className="text-xs opacity-90 mt-0.5">{rec.message}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Confidence Score Description */}
            <div className="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                <p>
                    <span className="font-medium">Confidence Score: </span>
                    {data.confidence_score.description}
                </p>
                <p className="mt-1 text-gray-400">
                    Based on {data.confidence_score.months_of_data || 6} months of income data
                </p>
            </div>
        </Card>
    );
}
