import { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { format, startOfMonth, endOfMonth } from 'date-fns';

import Authenticated from '@/Layouts/Authenticated';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FileXls, FileCsv, FileText, ChartBar, Download, ArrowsClockwise, Check, Clock } from '@phosphor-icons/react';
import axios from 'axios';

interface Props {
    auth: { user: { name: string; email: string; role?: string } };
}

interface ExportStatus {
    exportId: string;
    filename: string;
    status: 'pending' | 'ready' | 'error';
    message?: string;
}

export default function Index({ auth }: Props) {
    // Transaction export state
    const [transactionFormat, setTransactionFormat] = useState('xlsx');
    const [transactionStartDate, setTransactionStartDate] = useState('');
    const [transactionEndDate, setTransactionEndDate] = useState('');
    const [isExporting, setIsExporting] = useState(false);
    const [exportStatus, setExportStatus] = useState<ExportStatus | null>(null);

    // Report export state
    const [reportFormat, setReportFormat] = useState('xlsx');
    const [reportStartDate, setReportStartDate] = useState('');
    const [reportEndDate, setReportEndDate] = useState('');

    // Set default dates to current month on mount
    useEffect(() => {
        const now = new Date();
        const start = format(startOfMonth(now), 'yyyy-MM-dd');
        const end = format(endOfMonth(now), 'yyyy-MM-dd');
        
        setTransactionStartDate(start);
        setTransactionEndDate(end);
        setReportStartDate(start);
        setReportEndDate(end);
    }, []);

    // Poll for export status
    useEffect(() => {
        if (!exportStatus || exportStatus.status !== 'pending') return;

        const interval = setInterval(async () => {
            try {
                const response = await axios.get(route('exports.status', { filename: exportStatus.filename }));
                if (response.data.ready) {
                    setExportStatus(prev => prev ? { ...prev, status: 'ready' } : null);
                    // Auto-download when ready
                    window.location.href = response.data.download_url;
                }
            } catch (error) {
                console.error('Failed to check export status:', error);
            }
        }, 2000);

        return () => clearInterval(interval);
    }, [exportStatus]);

    const handleTransactionExport = async () => {
        setIsExporting(true);
        
        try {
            const params = new URLSearchParams();
            params.append('format', transactionFormat);
            
            // Use default one month window if dates not provided
            if (transactionStartDate) params.append('start_date', transactionStartDate);
            if (transactionEndDate) params.append('end_date', transactionEndDate);

            const response = await axios.post(route('exports.transactions'), params);
            
            if (response.data.success) {
                setExportStatus({
                    exportId: response.data.export_id,
                    filename: `transactions_${response.data.export_id}.${transactionFormat}`,
                    status: 'pending',
                });
            }
        } catch (error) {
            console.error('Export failed:', error);
            alert('Failed to start export. Please try again.');
        } finally {
            setIsExporting(false);
        }
    };

    const handleReportExport = () => {
        const params = new URLSearchParams();
        params.append('format', reportFormat);
        
        // Use default one month window if dates not provided
        if (reportStartDate) params.append('start_date', reportStartDate);
        if (reportEndDate) params.append('end_date', reportEndDate);

        window.location.href = route('exports.report') + '?' + params.toString();
    };

    const clearExportStatus = () => {
        setExportStatus(null);
    };

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">Exports</h2>}>
            <Head title="Exports" />
            <div className="p-4 max-w-7xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Export Data</h1>
                    <Button variant="outline" asChild>
                        <Link href={route('reports.index')}>
                            <ChartBar className="mr-2 h-4 w-4" />
                            View Reports
                        </Link>
                    </Button>
                </div>

                {/* Export Status Notification */}
                {exportStatus && (
                    <Card className={exportStatus.status === 'ready' ? 'border-green-500 bg-green-50' : 'border-blue-500 bg-blue-50'}>
                        <CardContent className="py-4">
                            <div className="flex items-center gap-3">
                                {exportStatus.status === 'pending' ? (
                                    <>
                                        <ArrowsClockwise className="h-5 w-5 animate-spin text-blue-600" />
                                        <div className="flex-1">
                                            <p className="font-medium text-blue-900">Export in Progress</p>
                                            <p className="text-sm text-blue-700">Your transaction export is being processed...</p>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <Check className="h-5 w-5 text-green-600" />
                                        <div className="flex-1">
                                            <p className="font-medium text-green-900">Export Ready!</p>
                                            <p className="text-sm text-green-700">Your file has been downloaded.</p>
                                        </div>
                                        <Button variant="outline" size="sm" onClick={clearExportStatus}>
                                            Dismiss
                                        </Button>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Transaction Export Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-primary" />
                            <CardTitle>Export Transactions</CardTitle>
                        </div>
                        <CardDescription>
                            Download your transaction history in your preferred format. 
                            <span className="text-amber-600 font-medium block mt-1">
                                <Clock className="inline h-4 w-4 mr-1" />
                                Default: Current month ({format(startOfMonth(new Date()), 'MMM d')} - {format(endOfMonth(new Date()), 'MMM d, yyyy')})
                            </span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="transaction-format">Format</Label>
                                <Select value={transactionFormat} onValueChange={setTransactionFormat}>
                                    <SelectTrigger id="transaction-format">
                                        <SelectValue placeholder="Select format" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="xlsx">
                                            <div className="flex items-center gap-2">
                                                <FileXls className="h-4 w-4" />
                                                Excel (.xlsx)
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="csv">
                                            <div className="flex items-center gap-2">
                                                <FileCsv className="h-4 w-4" />
                                                CSV (.csv)
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Date Range (Optional)</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        type="date"
                                        placeholder="Start date"
                                        value={transactionStartDate}
                                        onChange={(e) => setTransactionStartDate(e.target.value)}
                                    />
                                    <span className="text-muted-foreground">to</span>
                                    <Input
                                        type="date"
                                        placeholder="End date"
                                        value={transactionEndDate}
                                        onChange={(e) => setTransactionEndDate(e.target.value)}
                                        min={transactionStartDate}
                                    />
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Leave empty to export current month only
                                </p>
                            </div>
                        </div>

                        <Button 
                            onClick={handleTransactionExport} 
                            className="w-full md:w-auto"
                            disabled={isExporting || !!exportStatus}
                        >
                            {isExporting ? (
                                <>
                                    <ArrowsClockwise className="mr-2 h-4 w-4 animate-spin" />
                                    Processing...
                                </>
                            ) : (
                                <>
                                    <Download className="mr-2 h-4 w-4" />
                                    Export Transactions
                                </>
                            )}
                        </Button>
                    </CardContent>
                </Card>

                {/* Report Export Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <ChartBar className="h-5 w-5 text-primary" />
                            <CardTitle>Export Financial Report</CardTitle>
                        </div>
                        <CardDescription>
                            Download a comprehensive financial report with income, expenses, and summaries.
                            <span className="text-amber-600 font-medium block mt-1">
                                <Clock className="inline h-4 w-4 mr-1" />
                                Default: Current month
                            </span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="report-format">Format</Label>
                                <Select value={reportFormat} onValueChange={setReportFormat}>
                                    <SelectTrigger id="report-format">
                                        <SelectValue placeholder="Select format" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="xlsx">
                                            <div className="flex items-center gap-2">
                                                <FileXls className="h-4 w-4" />
                                                Excel (.xlsx)
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="csv">
                                            <div className="flex items-center gap-2">
                                                <FileText className="h-4 w-4" />
                                                CSV (.csv)
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label>Date Range (Optional)</Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        type="date"
                                        placeholder="Start date"
                                        value={reportStartDate}
                                        onChange={(e) => setReportStartDate(e.target.value)}
                                    />
                                    <span className="text-muted-foreground">to</span>
                                    <Input
                                        type="date"
                                        placeholder="End date"
                                        value={reportEndDate}
                                        onChange={(e) => setReportEndDate(e.target.value)}
                                        min={reportStartDate}
                                    />
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Leave empty to export current month only
                                </p>
                            </div>
                        </div>

                        <Button onClick={handleReportExport} className="w-full md:w-auto">
                            <Download className="mr-2 h-4 w-4" />
                            Download Report
                        </Button>
                    </CardContent>
                </Card>

                {/* Quick Tips */}
                <Card className="bg-muted/50">
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Export Tips</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="text-sm text-muted-foreground space-y-2 list-disc list-inside">
                            <li><strong>Excel (.xlsx)</strong> format is recommended for viewing and analysis in spreadsheet applications.</li>
                            <li><strong>CSV format</strong> is best for importing into other systems or for automated processing.</li>
                            <li>Transaction exports are processed in the background and will auto-download when ready.</li>
                            <li>Report exports are generated immediately and download directly.</li>
                            <li><strong>Default behavior:</strong> If no date range is selected, exports will include the current month only.</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </Authenticated>
    );
}
