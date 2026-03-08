import { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { getSpendingTransactions } from '@/Api/spending';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import LoadingView from "@/components/Global/LoadingView";
import { formatNumber, getAppCurrency } from '@/Utils';
import { Trash } from '@phosphor-icons/react';
import { format } from 'date-fns';

interface SpendingTransactionTableProps {
    dateRange: DateRange | undefined;
    type?: string;
    onDelete?: (id: number) => void;
}

const TYPE_STYLES: Record<string, string> = {
    home: 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100',
    personal: 'bg-blue-100 text-blue-800 hover:bg-blue-100',
};

export default function SpendingTransactionTable({ dateRange, type = 'all', onDelete }: SpendingTransactionTableProps) {
    const [transactions, setTransactions] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [currentPage, setCurrentPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);

    useEffect(() => {
        if (!dateRange?.from || !dateRange?.to) return;

        setLoading(true);
        setCurrentPage(1);
        const startDate = dateRange.from.toISOString().split('T')[0];
        const endDate = dateRange.to.toISOString().split('T')[0];

        getSpendingTransactions(type === 'all' ? null : type, startDate, endDate, 1, 10)
            .then((response) => {
                setTransactions(response.data.transactions.data);
                setHasMore(response.data.transactions.next_page_url !== null);
                setLoading(false);
            })
            .catch(console.error);
    }, [dateRange, type]);

    const loadMore = () => {
        if (!dateRange?.from || !dateRange?.to) return;

        const startDate = dateRange.from.toISOString().split('T')[0];
        const endDate = dateRange.to.toISOString().split('T')[0];
        const nextPage = currentPage + 1;

        getSpendingTransactions(type === 'all' ? null : type, startDate, endDate, nextPage, 10)
            .then((response) => {
                setTransactions(prev => [...prev, ...response.data.transactions.data]);
                setHasMore(response.data.transactions.next_page_url !== null);
                setCurrentPage(nextPage);
            })
            .catch(console.error);
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return format(date, 'MMM d');
    };

    if (loading) {
        return (
            <Card>
                <CardContent className="p-8">
                    <LoadingView />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardContent className="p-0">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Date</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Description</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Category</th>
                                <th className="text-left py-3 px-4 text-sm font-medium text-gray-500">Type</th>
                                <th className="text-right py-3 px-4 text-sm font-medium text-gray-500">Amount</th>
                                <th className="w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-8 text-center text-gray-500">
                                        No transactions found
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((transaction) => (
                                    <tr key={transaction.id} className="border-b border-gray-100 hover:bg-gray-50">
                                        <td className="py-3 px-4 text-sm text-gray-600">
                                            {formatDate(transaction.created_at)}
                                        </td>
                                        <td className="py-3 px-4 text-sm text-gray-900">
                                            {transaction.description || transaction.brand?.name || 'Unknown'}
                                        </td>
                                        <td className="py-3 px-4">
                                            {transaction.brand?.category ? (
                                                <Badge 
                                                    variant="secondary"
                                                    className="text-xs font-normal"
                                                    style={{
                                                        backgroundColor: transaction.brand.category.color ? `${transaction.brand.category.color}20` : undefined,
                                                        color: transaction.brand.category.color,
                                                        borderColor: transaction.brand.category.color,
                                                    }}
                                                >
                                                    {transaction.brand.category.name}
                                                </Badge>
                                            ) : (
                                                <span className="text-sm text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4">
                                            {transaction.type ? (
                                                <Badge 
                                                    variant="secondary"
                                                    className={`text-xs font-normal capitalize ${TYPE_STYLES[transaction.type] || ''}`}
                                                >
                                                    {transaction.type}
                                                </Badge>
                                            ) : (
                                                <span className="text-sm text-gray-400">-</span>
                                            )}
                                        </td>
                                        <td className="py-3 px-4 text-sm text-gray-900 text-right font-medium">
                                            {getAppCurrency()} {formatNumber(transaction.amount, 2)}
                                        </td>
                                        <td className="py-3 px-4">
                                            {onDelete && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-gray-400 hover:text-red-500"
                                                    onClick={() => onDelete(transaction.id)}
                                                >
                                                    <Trash size={18} />
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                {hasMore && (
                    <div className="p-4 border-t border-gray-100">
                        <Button 
                            variant="outline" 
                            className="w-full"
                            onClick={loadMore}
                        >
                            Load More
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
