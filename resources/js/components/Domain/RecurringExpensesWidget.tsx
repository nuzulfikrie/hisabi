import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { 
    ClockIcon, 
    ExclamationCircleIcon,
    CalendarIcon,
    CurrencyDollarIcon
} from '@heroicons/react/solid';

import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import LoadingView from "../Global/LoadingView";
import { formatNumber, getAppCurrency } from '@/Utils';
import { useInView } from '@/hooks/useInView';
import { format, differenceInDays, parseISO } from 'date-fns';

type Frequency = 'monthly' | 'quarterly' | 'annual';

interface RecurringExpense {
    id: string;
    brand: string;
    amount: number;
    frequency: Frequency;
    category: string;
    last_transaction: string;
    unused_days?: number;
    is_unused: boolean;
}

interface RecurringExpensesData {
    expenses: RecurringExpense[];
    total_monthly: number;
    total_quarterly: number;
    total_annual: number;
    unused_subscriptions: RecurringExpense[];
}

interface RecurringExpensesWidgetProps {
    dateRange: DateRange | undefined;
}

const fetchRecurringExpenses = async (dateRange: DateRange | undefined): Promise<RecurringExpensesData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/recurring-expenses?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

const getFrequencyLabel = (frequency: Frequency): string => {
    switch (frequency) {
        case 'monthly': return 'Monthly';
        case 'quarterly': return 'Quarterly';
        case 'annual': return 'Annual';
        default: return frequency;
    }
};

const getFrequencyIcon = (frequency: Frequency) => {
    switch (frequency) {
        case 'monthly':
            return <div className="w-2 h-2 rounded-full bg-blue-500" />;
        case 'quarterly':
            return <div className="w-2 h-2 rounded-full bg-purple-500" />;
        case 'annual':
            return <div className="w-2 h-2 rounded-full bg-indigo-500" />;
    }
};

const groupByFrequency = (expenses: RecurringExpense[]) => {
    return {
        monthly: expenses.filter(e => e.frequency === 'monthly'),
        quarterly: expenses.filter(e => e.frequency === 'quarterly'),
        annual: expenses.filter(e => e.frequency === 'annual'),
    };
};

export default function RecurringExpensesWidget({ dateRange }: RecurringExpensesWidgetProps) {
    const [data, setData] = useState<RecurringExpensesData | null>(null);
    const [expandedGroup, setExpandedGroup] = useState<Frequency | null>('monthly');
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchRecurringExpenses(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[400px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    const grouped = groupByFrequency(data.expenses);

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-start mb-4">
                <div>
                    <div className="flex items-center gap-2">
                        <ClockIcon className="h-5 w-5 text-gray-500" />
                        <h3 className="text-base text-gray-600">Recurring Expenses</h3>
                    </div>
                    <div className="flex items-center gap-1 mt-1">
                        <CurrencyDollarIcon className="h-3 w-3 text-gray-400" />
                        <span className="text-xs text-gray-500">
                            Monthly total: <span className="font-medium text-gray-700">
                                {getAppCurrency()} {formatNumber(data.total_monthly)}
                            </span>
                        </span>
                    </div>
                </div>
                {(data.unused_subscriptions || []).length > 0 && (
                    <Badge variant="destructive" className="text-xs">
                        {data.unused_subscriptions.length} unused
                    </Badge>
                )}
            </div>

            {/* Unused Subscriptions Alert */}
            {(data.unused_subscriptions || []).length > 0 && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div className="flex items-start gap-2">
                        <ExclamationCircleIcon className="h-4 w-4 text-red-500 mt-0.5 flex-shrink-0" />
                        <div>
                            <p className="text-xs font-medium text-red-800">
                                Unused Subscriptions Detected
                            </p>
                            <div className="mt-1 space-y-1">
                                {(data.unused_subscriptions || []).slice(0, 2).map(sub => (
                                    <p key={sub.id} className="text-xs text-red-700">
                                        • {sub.brand} - {sub.unused_days} days unused
                                    </p>
                                ))}
                                {(data.unused_subscriptions || []).length > 2 && (
                                    <p className="text-xs text-red-600">
                                        + {data.unused_subscriptions.length - 2} more
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Frequency Tabs */}
            <div className="flex gap-2 mb-4">
                {(['monthly', 'quarterly', 'annual'] as Frequency[]).map(freq => {
                    const count = grouped[freq].length;
                    const total = freq === 'monthly' ? data.total_monthly : 
                                 freq === 'quarterly' ? data.total_quarterly : data.total_annual;
                    return (
                        <button
                            key={freq}
                            onClick={() => setExpandedGroup(expandedGroup === freq ? null : freq)}
                            className={`flex-1 py-2 px-3 rounded-lg text-left transition-colors ${
                                expandedGroup === freq 
                                    ? 'bg-gray-100 border border-gray-200' 
                                    : 'hover:bg-gray-50 border border-transparent'
                            }`}
                        >
                            <div className="flex items-center gap-1.5">
                                {getFrequencyIcon(freq)}
                                <span className="text-xs font-medium capitalize">{freq}</span>
                            </div>
                            <p className="text-sm font-semibold mt-1">
                                {getAppCurrency()} {formatNumber(total)}
                            </p>
                            <p className="text-xs text-gray-500">{count} items</p>
                        </button>
                    );
                })}
            </div>

            {/* Expenses List */}
            <div className="space-y-2 max-h-[200px] overflow-y-auto">
                {expandedGroup && grouped[expandedGroup].length === 0 ? (
                    <p className="text-sm text-gray-500 text-center py-4">
                        No {expandedGroup} expenses found
                    </p>
                ) : (
                    expandedGroup && grouped[expandedGroup].map(expense => (
                        <div
                            key={expense.id}
                            className={`p-3 rounded-lg border ${
                                expense.is_unused 
                                    ? 'bg-red-50 border-red-200' 
                                    : 'bg-gray-50 border-gray-100'
                            }`}
                        >
                            <div className="flex justify-between items-start">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-medium text-gray-800">
                                            {expense.brand}
                                        </p>
                                        {expense.is_unused && (
                                            <Badge variant="destructive" className="text-[10px] px-1">
                                                Unused
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="text-xs text-gray-500">{expense.category}</p>
                                    <div className="flex items-center gap-1 mt-1">
                                        <CalendarIcon className="h-3 w-3 text-gray-400" />
                                        <span className="text-xs text-gray-400">
                                            Last: {format(parseISO(expense.last_transaction), 'MMM d, yyyy')}
                                        </span>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-semibold text-gray-800">
                                        {getAppCurrency()} {formatNumber(expense.amount)}
                                    </p>
                                    <p className="text-xs text-gray-500">
                                        {getFrequencyLabel(expense.frequency)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </Card>
    );
}
