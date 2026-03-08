import { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { getSpendingSummary } from '@/Api/spending';
import { formatNumber, getAppCurrency } from '@/Utils';

interface SpendingTotalProps {
    dateRange: DateRange | undefined;
    type?: string;
}

export default function SpendingTotal({ dateRange, type = 'all' }: SpendingTotalProps) {
    const [total, setTotal] = useState<number>(0);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!dateRange?.from || !dateRange?.to) return;

        setLoading(true);
        const startDate = dateRange.from.toISOString().split('T')[0];
        const endDate = dateRange.to.toISOString().split('T')[0];

        getSpendingSummary(type === 'all' ? null : type, startDate, endDate)
            .then((response) => {
                setTotal(response.data.total);
                setLoading(false);
            })
            .catch(console.error);
    }, [dateRange, type]);

    if (loading) {
        return (
            <div className="text-right">
                <p className="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                <p className="text-2xl font-bold text-gray-400">...</p>
            </div>
        );
    }

    return (
        <div className="text-right">
            <p className="text-xs text-gray-500 uppercase tracking-wide">Total</p>
            <p className="text-3xl font-bold text-gray-900">
                {getAppCurrency()}{formatNumber(total, 2)}
            </p>
        </div>
    );
}
