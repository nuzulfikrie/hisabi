import React, { useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { 
    ExclamationCircleIcon, 
    CheckCircleIcon, 
    InformationCircleIcon,
    TrendingUpIcon,
    CashIcon
} from '@heroicons/react/solid';

import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import LoadingView from "../Global/LoadingView";
import { useInView } from '@/hooks/useInView';
import { formatNumber, getAppCurrency } from '@/Utils';

type AlertType = 'budget_warning' | 'budget_exceeded' | 'spending_increase' | 'positive';

interface Alert {
    id: string;
    type: AlertType;
    title: string;
    message: string;
    category?: string;
    amount?: number;
    percentage?: number;
}

interface SpendingAlertsData {
    alerts: Alert[];
    total_alerts: number;
}

interface SpendingAlertsWidgetProps {
    dateRange: DateRange | undefined;
}

const fetchSpendingAlerts = async (dateRange: DateRange | undefined): Promise<SpendingAlertsData> => {
    const formatDate = (date: Date | undefined) => date ? date.toISOString().split('T')[0] : '';
    const params = new URLSearchParams({
        from: formatDate(dateRange?.from),
        to: formatDate(dateRange?.to),
    });
    const response = await fetch(`/api/v1/metrics/spending-alerts?${params}`, {
        method: 'GET',
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    return (await response.json()).data;
};

const getAlertIcon = (type: AlertType) => {
    switch (type) {
        case 'budget_exceeded':
            return <ExclamationCircleIcon className="h-5 w-5 text-red-500" />;
        case 'budget_warning':
            return <InformationCircleIcon className="h-5 w-5 text-yellow-500" />;
        case 'spending_increase':
            return <TrendingUpIcon className="h-5 w-5 text-orange-500" />;
        case 'positive':
            return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
        default:
            return <InformationCircleIcon className="h-5 w-5 text-gray-500" />;
    }
};

const getAlertColor = (type: AlertType): string => {
    switch (type) {
        case 'budget_exceeded':
            return 'bg-red-50 border-red-200';
        case 'budget_warning':
            return 'bg-yellow-50 border-yellow-200';
        case 'spending_increase':
            return 'bg-orange-50 border-orange-200';
        case 'positive':
            return 'bg-green-50 border-green-200';
        default:
            return 'bg-gray-50 border-gray-200';
    }
};

const getAlertBadge = (type: AlertType) => {
    switch (type) {
        case 'budget_exceeded':
            return <Badge variant="destructive" className="text-xs">Critical</Badge>;
        case 'budget_warning':
            return <Badge variant="secondary" className="text-xs bg-yellow-100 text-yellow-800">Warning</Badge>;
        case 'spending_increase':
            return <Badge variant="secondary" className="text-xs bg-orange-100 text-orange-800">Notice</Badge>;
        case 'positive':
            return <Badge variant="secondary" className="text-xs bg-green-100 text-green-800">Good</Badge>;
    }
};

export default function SpendingAlertsWidget({ dateRange }: SpendingAlertsWidgetProps) {
    const [data, setData] = useState<SpendingAlertsData | null>(null);
    const [ref, isInView] = useInView();

    useEffect(() => {
        if (!isInView || !dateRange?.from || !dateRange?.to) return;

        fetchSpendingAlerts(dateRange)
            .then(setData)
            .catch(console.error);
    }, [dateRange, isInView]);

    if (data == null) {
        return (
            <div ref={ref}>
                <Card className="relative h-[300px]">
                    <LoadingView />
                </Card>
            </div>
        );
    }

    const displayAlerts = (data.alerts || []).slice(0, 5);

    return (
        <Card className="relative p-6">
            <div className="flex justify-between items-center mb-4">
                <div className="flex items-center gap-2">
                    <h3 className="text-base text-gray-600">Spending Alerts</h3>
                    {data.total_alerts > 0 && (
                        <Badge variant="secondary" className="text-xs">
                            {data.total_alerts}
                        </Badge>
                    )}
                </div>
                {(data.alerts || []).length > 5 && (
                    <button className="text-xs text-blue-600 hover:text-blue-800">
                        View all
                    </button>
                )}
            </div>

            {displayAlerts.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-8 text-center">
                    <div className="bg-green-100 p-3 rounded-full mb-3">
                        <CheckCircleIcon className="h-6 w-6 text-green-500" />
                    </div>
                    <p className="text-sm text-gray-600">All clear! No spending alerts.</p>
                    <p className="text-xs text-gray-400 mt-1">Your spending is on track.</p>
                </div>
            ) : (
                <div className="space-y-3">
                    {displayAlerts.map((alert) => (
                        <div
                            key={alert.id}
                            className={`p-3 rounded-lg border ${getAlertColor(alert.type)} flex items-start gap-3`}
                        >
                            <div className="flex-shrink-0 mt-0.5">
                                {getAlertIcon(alert.type)}
                            </div>
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center gap-2 mb-1">
                                    <p className="text-sm font-medium text-gray-800 truncate">
                                        {alert.title}
                                    </p>
                                    {getAlertBadge(alert.type)}
                                </div>
                                <p className="text-xs text-gray-600">{alert.message}</p>
                                {alert.amount !== undefined && alert.amount > 0 && (
                                    <div className="flex items-center gap-2 mt-2">
                                        <CashIcon className="h-3 w-3 text-gray-400" />
                                        <span className="text-xs font-medium text-gray-700">
                                            {getAppCurrency()} {formatNumber(alert.amount)}
                                        </span>
                                        {alert.percentage !== undefined && (
                                            <span className="text-xs text-gray-500">
                                                ({alert.percentage}% of budget)
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </Card>
    );
}
