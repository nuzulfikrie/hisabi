import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

import Authenticated from '@/Layouts/Authenticated';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ChartBar, Download, Funnel, FileXls, FileCsv } from '@phosphor-icons/react';
import { formatNumber, getAppCurrency } from '@/Utils';

interface ReportSection {
    [key: string]: number | string | Array<Record<string, unknown>> | Record<string, number | string>;
}

interface Props {
    auth: { user: { name: string; email: string; role?: string } };
    sections: Record<string, ReportSection>;
    currency: string;
    range: string;
    filters: {
        start_date: string | null;
        end_date: string | null;
    };
}

export default function Index({ auth, sections, currency, range, filters }: Props) {
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const handleApplyFilters = () => {
        const params: Record<string, string> = {};
        if (startDate) params.start_date = startDate;
        if (endDate) params.end_date = endDate;
        router.get(route('reports.index'), params);
    };

    const handleClearFilters = () => {
        setStartDate('');
        setEndDate('');
        router.get(route('reports.index'));
    };

    const handleExport = (format: string) => {
        const params = new URLSearchParams();
        params.append('format', format);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);

        window.location.href = route('exports.report') + '?' + params.toString();
    };

    // Helper to format section data
    const formatValue = (value: unknown): string => {
        if (typeof value === 'number') {
            return formatNumber(value);
        }
        return String(value);
    };

    const isArrayOfRecords = (value: unknown): value is Array<Record<string, unknown>> => {
        return Array.isArray(value) && value.length > 0 && typeof value[0] === 'object';
    };

    const isRecord = (value: unknown): value is Record<string, number | string> => {
        return typeof value === 'object' && value !== null && !Array.isArray(value);
    };

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">Financial Report</h2>}>
            <Head title="Financial Report" />
            <div className="p-4 max-w-7xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold">Financial Report</h1>
                        <p className="text-muted-foreground">
                            Period: {range} | Currency: {currency}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('exports.index')}>
                                <Download className="mr-2 h-4 w-4" />
                                Export Center
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Date Filter */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Funnel className="h-5 w-5 text-primary" />
                            <CardTitle className="text-base">Date Range Filter</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col md:flex-row gap-4 items-end">
                            <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="start-date">Start Date</Label>
                                    <Input
                                        id="start-date"
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="end-date">End Date</Label>
                                    <Input
                                        id="end-date"
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                        min={startDate}
                                    />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button onClick={handleApplyFilters}>Apply Filter</Button>
                                <Button variant="ghost" onClick={handleClearFilters}>
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Export Actions */}
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" onClick={() => handleExport('xlsx')}>
                        <FileXls className="mr-2 h-4 w-4" />
                        Export as Excel
                    </Button>
                    <Button variant="outline" onClick={() => handleExport('csv')}>
                        <FileCsv className="mr-2 h-4 w-4" />
                        Export as CSV
                    </Button>
                </div>

                {/* Report Sections */}
                {Object.entries(sections).map(([sectionName, sectionData]) => (
                    <Card key={sectionName}>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <ChartBar className="h-5 w-5 text-primary" />
                                <CardTitle className="capitalize">
                                    {sectionName.replace(/_/g, ' ')}
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {isArrayOfRecords(sectionData) ? (
                                // Array of records - render as table
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                {Object.keys(sectionData[0]).map((key) => (
                                                    <TableHead key={key} className="capitalize">
                                                        {key.replace(/_/g, ' ')}
                                                    </TableHead>
                                                ))}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {sectionData.map((row, idx) => (
                                                <TableRow key={idx}>
                                                    {Object.entries(row).map(([key, value]) => (
                                                        <TableCell key={key}>
                                                            {typeof value === 'number' &&
                                                            (key.includes('amount') ||
                                                                key.includes('total') ||
                                                                key.includes('sum') ||
                                                                key.includes('price'))
                                                                ? `${getAppCurrency()} ${formatNumber(value)}`
                                                                : String(value)}
                                                        </TableCell>
                                                    ))}
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : isRecord(sectionData) ? (
                                // Key-value pairs - render as table
                                <Table>
                                    <TableBody>
                                        {Object.entries(sectionData).map(([key, value]) => {
                                            if (isRecord(value)) {
                                                // Nested record - render as sub-section
                                                return (
                                                    <TableRow key={key} className="bg-muted/50">
                                                        <TableCell
                                                            colSpan={2}
                                                            className="font-medium capitalize"
                                                        >
                                                            {key.replace(/_/g, ' ')}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            }
                                            return (
                                                <TableRow key={key}>
                                                    <TableCell className="font-medium capitalize w-1/2">
                                                        {key.replace(/_/g, ' ')}
                                                    </TableCell>
                                                    <TableCell>
                                                        {typeof value === 'number' &&
                                                        (key.includes('amount') ||
                                                            key.includes('total') ||
                                                            key.includes('sum') ||
                                                            key.includes('balance'))
                                                            ? `${getAppCurrency()} ${formatNumber(value)}`
                                                            : formatValue(value)}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    No data available for this section.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                ))}

                {Object.keys(sections).length === 0 && (
                    <Card className="bg-muted/50">
                        <CardContent className="py-8 text-center">
                            <ChartBar className="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" />
                            <p className="text-muted-foreground">
                                No report data available for the selected period.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </Authenticated>
    );
}
