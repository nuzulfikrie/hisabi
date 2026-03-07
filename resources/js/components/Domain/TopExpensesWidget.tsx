import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { ArrowRightIcon, DocumentTextIcon } from '@heroicons/react/solid';
import { Link } from '@inertiajs/react';

import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import LoadingView from "../Global/LoadingView";
import { formatNumber, getAppCurrency, getTailwindColor } from '@/Utils';
import { useInView } from '@/hooks/useInView';
import { format } from 'date-fns';

interface Transaction {
    id: string;
    brand: string;
    amount: number;
    category: string;
    category_color?: string;
    date: string;
}

interface TopExpensesData {
    transactions: Transaction[];
    total_count: number;
}

interface TopExpensesWidgetProps {
    dateRange: DateRange | undefined;
    limit?: number;
}

const fetchTopExpenses = async (dateRange: DateRange | undefined): Promise<TopExpensesData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/top-expenses?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

export default function TopExpensesWidget({ dateRange, limit = 5 }: TopExpensesWidgetProps) {
    const [data, setData] = useState<TopExpensesData | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchTopExpenses(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[350px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    const displayTransactions = data.transactions.slice(0, limit);

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-center mb-4">
                <div className="flex items-center gap-2">
                    <DocumentTextIcon className="h-5 w-5 text-gray-500" />
                    <h3 className="text-base text-gray-600">Top Expenses</h3>
                </div>
                <Link
                    href="/transactions"
                    className="flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 transition-colors"
                >
                    View All
                    <ArrowRightIcon className="h-3 w-3" />
                </Link>
            </div>

            {displayTransactions.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-8 text-center">
                    <p className="text-sm text-gray-500">No expenses found in this period</p>
                </div>
            ) : (
                <div className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-100">
                                    <th className="text-left text-xs font-medium text-gray-500 py-2">Brand</th>
                                    <th className="text-left text-xs font-medium text-gray-500 py-2">Category</th>
                                    <th className="text-left text-xs font-medium text-gray-500 py-2">Date</th>
                                    <th className="text-right text-xs font-medium text-gray-500 py-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {displayTransactions.map((transaction, index) => (
                                    <tr
                                        key={transaction.id}
                                        className="border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors"
                                    >
                                        <td className="py-3">
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs text-gray-400 w-4">#{index + 1}</span>
                                                <span className="text-sm font-medium text-gray-800 truncate max-w-[120px]">
                                                    {transaction.brand}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="py-3">
                                            <Badge 
                                                variant="secondary" 
                                                className="text-xs"
                                                style={{
                                                    backgroundColor: transaction.category_color ? `${transaction.category_color}20` : undefined,
                                                    color: transaction.category_color || undefined,
                                                    borderColor: transaction.category_color ? `${transaction.category_color}40` : undefined,
                                                }}
                                            >
                                                {transaction.category}
                                            </Badge>
                                        </td>
                                        <td className="py-3">
                                            <span className="text-xs text-gray-500">
                                                {format(new Date(transaction.date), 'MMM d')}
                                            </span>
                                        </td>
                                        <td className="py-3 text-right">
                                            <span className="text-sm font-semibold text-red-600">
                                                {getAppCurrency()} {formatNumber(transaction.amount)}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {data.total_count > limit && (
                <div className="mt-4 pt-3 border-t border-gray-100 text-center">
                    <Link
                        href="/transactions"
                        className="text-xs text-gray-500 hover:text-gray-700"
                    >
                        + {data.total_count - limit} more transactions
                    </Link>
                </div>
            )}
        </Card>
    );
}
