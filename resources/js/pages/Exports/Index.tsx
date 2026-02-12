import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';

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
import { FileSpreadsheet, FileText, FileBarChart, Download } from '@phosphor-icons/react';

interface Props {
    auth: { user: { name: string; email: string; role?: string } };
}

export default function Index({ auth }: Props) {
    // Transaction export state
    const [transactionFormat, setTransactionFormat] = useState('xlsx');
    const [transactionStartDate, setTransactionStartDate] = useState('');
    const [transactionEndDate, setTransactionEndDate] = useState('');

    // Report export state
    const [reportFormat, setReportFormat] = useState('xlsx');
    const [reportStartDate, setReportStartDate] = useState('');
    const [reportEndDate, setReportEndDate] = useState('');

    const handleTransactionExport = () => {
        const params = new URLSearchParams();
        params.append('format', transactionFormat);
        if (transactionStartDate) params.append('start_date', transactionStartDate);
        if (transactionEndDate) params.append('end_date', transactionEndDate);

        window.location.href = route('exports.transactions') + '?' + params.toString();
    };

    const handleReportExport = () => {
        const params = new URLSearchParams();
        params.append('format', reportFormat);
        if (reportStartDate) params.append('start_date', reportStartDate);
        if (reportEndDate) params.append('end_date', reportEndDate);

        window.location.href = route('exports.report') + '?' + params.toString();
    };

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">Exports</h2>}>
            <Head title="Exports" />
            <div className="p-4 max-w-7xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Export Data</h1>
                    <Button variant="outline" asChild>
                        <Link href={route('reports.index')}>
                            <FileBarChart className="mr-2 h-4 w-4" />
                            View Reports
                        </Link>
                    </Button>
                </div>

                {/* Transaction Export Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-primary" />
                            <CardTitle>Export Transactions</CardTitle>
                        </div>
                        <CardDescription>
                            Download your transaction history in your preferred format.
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
                                                <FileSpreadsheet className="h-4 w-4" />
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
                            </div>
                        </div>

                        <Button onClick={handleTransactionExport} className="w-full md:w-auto">
                            <Download className="mr-2 h-4 w-4" />
                            Download Transactions
                        </Button>
                    </CardContent>
                </Card>

                {/* Report Export Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <FileBarChart className="h-5 w-5 text-primary" />
                            <CardTitle>Export Financial Report</CardTitle>
                        </div>
                        <CardDescription>
                            Download a comprehensive financial report with income, expenses, and summaries.
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
                                                <FileSpreadsheet className="h-4 w-4" />
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
                            <li>Excel (.xlsx) format is recommended for viewing and analysis in spreadsheet applications.</li>
                            <li>CSV format is best for importing into other systems or for automated processing.</li>
                            <li>Leave date range empty to export all data.</li>
                            <li>Exported files include timestamps in their filenames for easy organization.</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </Authenticated>
    );
}
