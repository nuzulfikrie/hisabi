import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { ShieldCheckIcon, ExclamationIcon } from '@heroicons/react/solid';

import { Card } from '@/components/ui/card';
import LoadingView from "../Global/LoadingView";
import { formatNumber } from '@/Utils';
import { useInView } from '@/hooks/useInView';

interface EmergencyFundData {
    months_covered: number;
    monthly_expenses: number;
    current_fund: number;
    target_3_month: number;
    target_6_month: number;
    progress_3_month: number;
    progress_6_month: number;
}

interface EmergencyFundWidgetProps {
    dateRange: DateRange | undefined;
}

const fetchEmergencyFundStatus = async (dateRange: DateRange | undefined): Promise<EmergencyFundData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/emergency-fund-status?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

const getStatusColor = (months: number): string => {
    if (months > 6) return 'bg-green-500';
    if (months >= 3) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getStatusTextColor = (months: number): string => {
    if (months > 6) return 'text-green-600';
    if (months >= 3) return 'text-yellow-600';
    return 'text-red-600';
};

const getStatusLabel = (months: number): string => {
    if (months > 6) return 'Secure';
    if (months >= 3) return 'Adequate';
    return 'Low';
};

const getStatusIcon = (months: number) => {
    if (months >= 3) {
        return <ShieldCheckIcon className={`h-5 w-5 ${getStatusTextColor(months)}`} />;
    }
    return <ExclamationIcon className="h-5 w-5 text-red-500" />;
};

export default function EmergencyFundWidget({ dateRange }: EmergencyFundWidgetProps) {
    const [data, setData] = useState<EmergencyFundData | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchEmergencyFundStatus(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[240px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-start mb-4">
                <div>
                    <h3 className="text-base text-gray-600">Emergency Fund</h3>
                    <p className="text-xs text-gray-400 mt-1">Months of expenses covered</p>
                </div>
                <div className="flex items-center gap-2">
                    {getStatusIcon(data.months_covered)}
                    <span className={`text-sm font-medium ${getStatusTextColor(data.months_covered)}`}>
                        {getStatusLabel(data.months_covered)}
                    </span>
                </div>
            </div>

            {/* Main Stat */}
            <div className="flex items-baseline gap-2 mb-6">
                <span className={`text-5xl font-bold ${getStatusTextColor(data.months_covered)}`}>
                    {formatNumber(data.months_covered, '(0[.]0)')}
                </span>
                <span className="text-gray-500">months</span>
            </div>

            {/* Progress Bars */}
            <div className="space-y-4">
                {/* 3 Month Goal */}
                <div>
                    <div className="flex justify-between text-xs mb-1">
                        <span className="text-gray-500">3-month goal</span>
                        <span className="font-medium">
                            {Math.min(Math.round(data.progress_3_month), 100)}%
                        </span>
                    </div>
                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            className={`h-full ${data.progress_3_month >= 100 ? 'bg-green-500' : 'bg-yellow-500'} transition-all duration-500`}
                            style={{ width: `${Math.min(data.progress_3_month, 100)}%` }}
                        />
                    </div>
                </div>

                {/* 6 Month Goal */}
                <div>
                    <div className="flex justify-between text-xs mb-1">
                        <span className="text-gray-500">6-month goal</span>
                        <span className="font-medium">
                            {Math.min(Math.round(data.progress_6_month), 100)}%
                        </span>
                    </div>
                    <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            className={`h-full ${data.progress_6_month >= 100 ? 'bg-green-500' : data.progress_6_month >= 50 ? 'bg-yellow-500' : 'bg-red-500'} transition-all duration-500`}
                            style={{ width: `${Math.min(data.progress_6_month, 100)}%` }}
                        />
                    </div>
                </div>
            </div>

            {/* Context */}
            <div className="mt-4 p-3 bg-gray-50 rounded-lg">
                <p className="text-xs text-gray-600">
                    Based on monthly expenses of <span className="font-medium">
                        {formatNumber(data.monthly_expenses)}
                    </span>
                </p>
                {data.months_covered < 3 && (
                    <p className="text-xs text-red-600 mt-1">
                        ⚠️ Aim to save at least 3 months of expenses
                    </p>
                )}
                {data.months_covered >= 3 && data.months_covered < 6 && (
                    <p className="text-xs text-yellow-600 mt-1">
                        👍 Good start! Keep building toward 6 months
                    </p>
                )}
                {data.months_covered >= 6 && (
                    <p className="text-xs text-green-600 mt-1">
                        ✅ Excellent! You have a solid emergency fund
                    </p>
                )}
            </div>
        </Card>
    );
}
