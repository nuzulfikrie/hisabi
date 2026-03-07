import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { TrendingUpIcon, TrendingDownIcon, ArrowRightIcon } from '@heroicons/react/solid';

import { Card } from '@/components/ui/card';
import LoadingView from "../Global/LoadingView";
import { formatNumber, getAppCurrency } from '@/Utils';
import { useInView } from '@/hooks/useInView';

interface CashFlowData {
    income: number;
    expenses: number;
    net_cash_flow: number;
    previous_period: {
        income: number;
        expenses: number;
        net_cash_flow: number;
    };
}

interface CashFlowWidgetProps {
    dateRange: DateRange | undefined;
}

const fetchCashFlow = async (dateRange: DateRange | undefined): Promise<CashFlowData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/cash-flow?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

export default function CashFlowWidget({ dateRange }: CashFlowWidgetProps) {
    const [data, setData] = useState<CashFlowData | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchCashFlow(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[200px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    const getChangePercentage = (current: number, previous: number) => {
        if (!previous) return 0;
        return (((current - previous) / previous) * 100).toFixed(1);
    };

    const incomeChange = parseFloat(getChangePercentage(data.income, data.previous_period.income));
    const expenseChange = parseFloat(getChangePercentage(data.expenses, data.previous_period.expenses));
    const netChange = parseFloat(getChangePercentage(data.net_cash_flow, data.previous_period.net_cash_flow));

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-base text-gray-600">Cash Flow</h3>
            </div>

            <div className="grid grid-cols-3 gap-4">
                {/* Income */}
                <div className="text-center">
                    <p className="text-xs text-gray-500 mb-1">Income</p>
                    <p className="text-xl font-semibold text-green-600">
                        {getAppCurrency()} {formatNumber(data.income)}
                    </p>
                    <div className="flex items-center justify-center mt-1 text-xs">
                        {incomeChange >= 0 ? (
                            <TrendingUpIcon className="h-3 w-3 text-green-500 mr-1" />
                        ) : (
                            <TrendingDownIcon className="h-3 w-3 text-red-500 mr-1" />
                        )}
                        <span className={incomeChange >= 0 ? 'text-green-600' : 'text-red-600'}>
                            {Math.abs(incomeChange)}%
                        </span>
                    </div>
                </div>

                {/* Expenses */}
                <div className="text-center">
                    <p className="text-xs text-gray-500 mb-1">Expenses</p>
                    <p className="text-xl font-semibold text-red-600">
                        {getAppCurrency()} {formatNumber(data.expenses)}
                    </p>
                    <div className="flex items-center justify-center mt-1 text-xs">
                        {expenseChange >= 0 ? (
                            <TrendingUpIcon className="h-3 w-3 text-red-500 mr-1" />
                        ) : (
                            <TrendingDownIcon className="h-3 w-3 text-green-500 mr-1" />
                        )}
                        <span className={expenseChange >= 0 ? 'text-red-600' : 'text-green-600'}>
                            {Math.abs(expenseChange)}%
                        </span>
                    </div>
                </div>

                {/* Net Cash Flow */}
                <div className="text-center">
                    <p className="text-xs text-gray-500 mb-1">Net Flow</p>
                    <p className={`text-xl font-semibold ${data.net_cash_flow >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {getAppCurrency()} {formatNumber(Math.abs(data.net_cash_flow))}
                    </p>
                    <div className="flex items-center justify-center mt-1 text-xs">
                        {netChange >= 0 ? (
                            <TrendingUpIcon className="h-3 w-3 text-green-500 mr-1" />
                        ) : (
                            <TrendingDownIcon className="h-3 w-3 text-red-500 mr-1" />
                        )}
                        <span className={netChange >= 0 ? 'text-green-600' : 'text-red-600'}>
                            {Math.abs(netChange)}%
                        </span>
                    </div>
                </div>
            </div>

            {/* Visual Indicator */}
            <div className="mt-4 flex items-center justify-center">
                <div className="flex items-center gap-2 text-sm">
                    <span className="text-gray-500">vs Previous Period</span>
                    <ArrowRightIcon className="h-4 w-4 text-gray-400" />
                    <span className={`font-medium ${data.net_cash_flow >= data.previous_period.net_cash_flow ? 'text-green-600' : 'text-red-600'}`}>
                        {data.net_cash_flow >= data.previous_period.net_cash_flow ? 'Improved' : 'Declined'}
                    </span>
                </div>
            </div>
        </Card>
    );
}
