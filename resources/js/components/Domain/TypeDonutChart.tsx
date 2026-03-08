import { useEffect, useState, useRef } from 'react';
import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js';
import { DateRange } from 'react-day-picker';
import { getSpendingByType } from '@/Api/spending';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LoadingView from "@/components/Global/LoadingView";

Chart.register(DoughnutController, ArcElement, Tooltip);

interface TypeDonutChartProps {
    dateRange: DateRange | undefined;
}

const TYPE_COLORS: Record<string, string> = {
    home: '#4a7c59',      // Green from image
    personal: '#6b8cae',  // Blue from image
};

export default function TypeDonutChart({ dateRange }: TypeDonutChartProps) {
    const [data, setData] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const chartRef = useRef<Chart | null>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        if (!dateRange?.from || !dateRange?.to) return;

        setLoading(true);
        const startDate = dateRange.from.toISOString().split('T')[0];
        const endDate = dateRange.to.toISOString().split('T')[0];

        getSpendingByType(startDate, endDate)
            .then((response) => {
                setData(response.data.data);
                setLoading(false);
            })
            .catch(console.error);
    }, [dateRange]);

    useEffect(() => {
        if (!canvasRef.current || data.length === 0) return;

        if (chartRef.current) {
            chartRef.current.destroy();
        }

        const ctx = canvasRef.current.getContext('2d');
        if (!ctx) return;

        chartRef.current = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.type.charAt(0).toUpperCase() + item.type.slice(1)),
                datasets: [{
                    data: data.map(item => item.total),
                    backgroundColor: data.map(item => TYPE_COLORS[item.type] || '#9ca3af'),
                    borderWidth: 0,
                    cutout: '65%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = data[context.dataIndex];
                                return `${item.type.charAt(0).toUpperCase() + item.type.slice(1)}: ${item.percentage}%`;
                            }
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
                <CardTitle className="text-sm font-semibold text-gray-600 uppercase tracking-wide">Personal vs Home</CardTitle>
            </CardHeader>
            <CardContent className="pt-0">
                {loading ? (
                    <div className="h-64 flex items-center justify-center">
                        <LoadingView />
                    </div>
                ) : data.length === 0 ? (
                    <div className="h-64 flex items-center justify-center text-gray-500">
                        No data found
                    </div>
                ) : (
                    <div className="h-64 relative">
                        <canvas ref={canvasRef} />
                        {/* Labels positioned around the chart */}
                        <div className="absolute inset-0 pointer-events-none">
                            {data.map((item, index) => {
                                // Position labels at specific angles
                                const positions = [
                                    { top: '10%', right: '10%' },  // Personal (top right)
                                    { bottom: '10%', left: '10%' }, // Home (bottom left)
                                ];
                                const pos = positions[index] || {};
                                return (
                                    <div
                                        key={item.type}
                                        className="absolute text-sm font-medium"
                                        style={{
                                            ...pos,
                                            color: TYPE_COLORS[item.type] || '#666',
                                        }}
                                    >
                                        {item.type.charAt(0).toUpperCase() + item.type.slice(1)} {item.percentage}%
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
