import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';

import { Card } from '@/components/ui/card';
import LoadingView from "../Global/LoadingView";
import { formatNumber } from '@/Utils';
import { useInView } from '@/hooks/useInView';

interface SavingsRateData {
    savings_rate: number;
    target_rate: number;
    income: number;
    savings: number;
}

interface SavingsRateWidgetProps {
    dateRange: DateRange | undefined;
}

const fetchSavingsRate = async (dateRange: DateRange | undefined): Promise<SavingsRateData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/savings-rate?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

const getColorClass = (rate: number): string => {
    if (rate >= 20) return 'bg-green-500';
    if (rate >= 10) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getTextColorClass = (rate: number): string => {
    if (rate >= 20) return 'text-green-600';
    if (rate >= 10) return 'text-yellow-600';
    return 'text-red-600';
};

const getStatusLabel = (rate: number): string => {
    if (rate >= 20) return 'On Track 🎯';
    if (rate >= 10) return 'Getting There 📈';
    return 'Needs Attention ⚠️';
};

export default function SavingsRateWidget({ dateRange }: SavingsRateWidgetProps) {
    const [data, setData] = useState<SavingsRateData | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchSavingsRate(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[180px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    const progressPercentage = Math.min((data.savings_rate / data.target_rate) * 100, 100);

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-base text-gray-600">Savings Rate</h3>
                <span className={`text-xs font-medium px-2 py-1 rounded-full ${getColorClass(data.savings_rate)} text-white`}>
                    {getStatusLabel(data.savings_rate)}
                </span>
            </div>

            <div className="flex items-end gap-2 mb-4">
                <span className={`text-4xl font-bold ${getTextColorClass(data.savings_rate)}`}>
                    {formatNumber(data.savings_rate, '(0[.]0)')}%
                </span>
                <span className="text-sm text-gray-500 mb-1">of income saved</span>
            </div>

            {/* Progress Bar */}
            <div className="relative">
                <div className="flex justify-between text-xs text-gray-500 mb-1">
                    <span>0%</span>
                    <span className="font-medium">Target: {data.target_rate}%</span>
                </div>
                <div className="h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        className={`h-full ${getColorClass(data.savings_rate)} transition-all duration-500 ease-out`}
                        style={{ width: `${progressPercentage}%` }}
                    />
                </div>
                <div className="mt-2 text-xs text-gray-500">
                    {data.savings_rate >= data.target_rate ? (
                        <span className="text-green-600">✓ Target achieved! Keep it up!</span>
                    ) : (
                        <span>{formatNumber(data.target_rate - data.savings_rate, '(0[.]0)')}% more to reach target</span>
                    )}
                </div>
            </div>
        </Card>
    );
}
