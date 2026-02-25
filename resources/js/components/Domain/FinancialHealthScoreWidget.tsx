import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { HeartIcon } from '@heroicons/react/solid';

import { Card } from '@/components/ui/card';
import LoadingView from "../Global/LoadingView";
import { useInView } from '@/hooks/useInView';

interface FinancialHealthData {
    score: number;
    breakdown: {
        savings_rate: number;
        emergency_fund: number;
        debt_ratio: number;
        spending_stability: number;
        budget_adherence: number;
    };
}

interface FinancialHealthScoreWidgetProps {
    dateRange: DateRange | undefined;
}

const fetchFinancialHealthScore = async (dateRange: DateRange | undefined): Promise<FinancialHealthData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/financial-health-score?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

const getScoreColor = (score: number): string => {
    if (score >= 90) return 'text-emerald-500';
    if (score >= 70) return 'text-green-500';
    if (score >= 50) return 'text-yellow-500';
    return 'text-red-500';
};

const getScoreBgColor = (score: number): string => {
    if (score >= 90) return 'bg-emerald-100';
    if (score >= 70) return 'bg-green-100';
    if (score >= 50) return 'bg-yellow-100';
    return 'bg-red-100';
};

const getStatusLabel = (score: number): string => {
    if (score >= 90) return 'Excellent';
    if (score >= 70) return 'Good';
    if (score >= 50) return 'Fair';
    return 'Needs Attention';
};

const getStatusDescription = (score: number): string => {
    if (score >= 90) return 'Your finances are in great shape!';
    if (score >= 70) return 'You\'re doing well, room for improvement.';
    if (score >= 50) return 'Some areas need attention.';
    return 'Focus on building financial stability.';
};

export default function FinancialHealthScoreWidget({ dateRange }: FinancialHealthScoreWidgetProps) {
    const [data, setData] = useState<FinancialHealthData | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchFinancialHealthScore(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[220px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-start mb-4">
                <div>
                    <h3 className="text-base text-gray-600">Financial Health Score</h3>
                    <p className="text-xs text-gray-400 mt-1">Based on your savings, spending & budgets</p>
                </div>
                <div className={`p-2 rounded-full ${getScoreBgColor(data.score)}`}>
                    <HeartIcon className={`h-5 w-5 ${getScoreColor(data.score)}`} />
                </div>
            </div>

            <div className="flex items-center gap-6">
                {/* Score Circle */}
                <div className="relative">
                    <svg className="w-24 h-24 transform -rotate-90">
                        <circle
                            cx="48"
                            cy="48"
                            r="40"
                            stroke="#e5e7eb"
                            strokeWidth="8"
                            fill="none"
                        />
                        <circle
                            cx="48"
                            cy="48"
                            r="40"
                            stroke="currentColor"
                            strokeWidth="8"
                            fill="none"
                            strokeLinecap="round"
                            strokeDasharray={`${(data.score / 100) * 251.2} 251.2`}
                            className={`${getScoreColor(data.score)} transition-all duration-700`}
                        />
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center">
                        <span className={`text-2xl font-bold ${getScoreColor(data.score)}`}>
                            {data.score}
                        </span>
                    </div>
                </div>

                {/* Status */}
                <div className="flex-1">
                    <p className={`text-xl font-semibold ${getScoreColor(data.score)}`}>
                        {getStatusLabel(data.score)}
                    </p>
                    <p className="text-sm text-gray-500 mt-1">
                        {getStatusDescription(data.score)}
                    </p>

                    {/* Mini Breakdown */}
                    <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div className="flex items-center gap-1">
                            <span className="text-gray-400">Savings:</span>
                            <span className="font-medium">{data.breakdown.savings_rate}/20</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <span className="text-gray-400">Emergency:</span>
                            <span className="font-medium">{data.breakdown.emergency_fund}/25</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <span className="text-gray-400">Debt:</span>
                            <span className="font-medium">{data.breakdown.debt_ratio}/20</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <span className="text-gray-400">Budget:</span>
                            <span className="font-medium">{data.breakdown.budget_adherence}/20</span>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    );
}
