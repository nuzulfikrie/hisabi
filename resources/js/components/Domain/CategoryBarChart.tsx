import { useEffect, useState, useRef } from 'react';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';
import { DateRange } from 'react-day-picker';
import { getSpendingByCategory } from '@/Api/spending';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LoadingView from "@/components/Global/LoadingView";
import { formatNumber, getAppCurrency } from '@/Utils';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

interface CategoryBarChartProps {
    dateRange: DateRange | undefined;
    type?: string;
}

export default function CategoryBarChart({ dateRange, type = 'all' }: CategoryBarChartProps) {
    const [data, setData] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const chartRef = useRef<Chart | null>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        if (!dateRange?.from || !dateRange?.to) return;

        setLoading(true);
        const startDate = dateRange.from.toISOString().split('T')[0];
        const endDate = dateRange.to.toISOString().split('T')[0];

        getSpendingByCategory(type === 'all' ? null : type, startDate, endDate)
            .then((response) => {
                setData(response.data.categories);
                setLoading(false);
            })
            .catch(console.error);
    }, [dateRange, type]);

    useEffect(() => {
        if (!canvasRef.current || data.length === 0) return;

        if (chartRef.current) {
            chartRef.current.destroy();
        }

        const ctx = canvasRef.current.getContext('2d');
        if (!ctx) return;

        // Sort by amount ascending for bottom-to-top display
        const sortedData = [...data].sort((a, b) => a.total_amount - b.total_amount);

        chartRef.current = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sortedData.map(item => item.category_name),
                datasets: [{
                    data: sortedData.map(item => item.total_amount),
                    backgroundColor: sortedData.map(item => item.category_color || '#3b82f6'),
                    borderRadius: 4,
                    barThickness: 24,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${getAppCurrency()} ${formatNumber(context.raw as number)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            },
                            font: {
                                size: 11,
                            }
                        },
                        border: {
                            display: false,
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            font: {
                                size: 12,
                            }
                        },
                        border: {
                            display: false,
                        }
                    }
                }
            }
        });

        return () => {
            if (chartRef.current) {
                chartRef.current.destroy();
            }
        };
    }, [data]);

    return (
        <Card className="h-full">
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-semibold text-gray-600 uppercase tracking-wide">By Category</CardTitle>
            </CardHeader>
            <CardContent className="pt-0">
                {loading ? (
                    <div className="h-64 flex items-center justify-center">
                        <LoadingView />
                    </div>
                ) : data.length === 0 ? (
                    <div className="h-64 flex items-center justify-center text-gray-500">
                        No spending data found
                    </div>
                ) : (
                    <div className="h-64">
                        <canvas ref={canvasRef} />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
