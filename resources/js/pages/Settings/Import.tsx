import { useState, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    CaretLeftIcon,
    UploadIcon,
    DownloadIcon,
    FileCsv,
    FileXls,
    CheckCircleIcon,
    WarningCircleIcon,
    ArrowRightIcon,
    TrashIcon,
} from "@phosphor-icons/react";

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import Authenticated from "@/Layouts/Authenticated";
import { importCsv, importExcel, downloadTemplate } from '@/Api/import';

interface ImportError {
    row: number;
    error: string;
}

interface ImportResult {
    success: boolean;
    message: string;
    imported: number;
    errors: ImportError[];
}

export default function Import() {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [result, setResult] = useState<ImportResult | null>(null);
    const [error, setError] = useState<string>('');
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            validateAndSetFile(file);
        }
    };

    const validateAndSetFile = (file: File) => {
        const allowedTypes = [
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        const allowedExtensions = ['.csv', '.xlsx', '.xls'];

        const hasValidExtension = allowedExtensions.some(ext =>
            file.name.toLowerCase().endsWith(ext)
        );

        if (!hasValidExtension) {
            setError('Please select a CSV or Excel file (.csv, .xlsx, .xls)');
            return;
        }

        // 10MB max file size
        if (file.size > 10 * 1024 * 1024) {
            setError('File size must be less than 10MB');
            return;
        }

        setSelectedFile(file);
        setError('');
        setResult(null);
    };

    const handleDragOver = (event: React.DragEvent) => {
        event.preventDefault();
    };

    const handleDrop = (event: React.DragEvent) => {
        event.preventDefault();
        const file = event.dataTransfer.files?.[0];
        if (file) {
            validateAndSetFile(file);
        }
    };

    const handleUpload = async () => {
        if (!selectedFile) return;

        setIsUploading(true);
        setUploadProgress(0);
        setError('');
        setResult(null);

        try {
            const isExcel = selectedFile.name.toLowerCase().endsWith('.xlsx') ||
                           selectedFile.name.toLowerCase().endsWith('.xls');

            const response = isExcel
                ? await importExcel(selectedFile)
                : await importCsv(selectedFile);

            setResult(response.data);
            setSelectedFile(null);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        } catch (err: any) {
            const message = err.response?.data?.message || 'Import failed. Please try again.';
            const errors = err.response?.data?.errors || [];
            setError(message);
            setResult({
                success: false,
                message,
                imported: 0,
                errors,
            });
        } finally {
            setIsUploading(false);
            setUploadProgress(0);
        }
    };

    const handleDownloadTemplate = async (format: 'csv' | 'excel') => {
        try {
            const response = await downloadTemplate(format);
            const blob = new Blob([response.data]);
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = format === 'csv' ? 'hisabi_import_template.csv' : 'hisabi_import_template.xlsx';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        } catch (err) {
            setError('Failed to download template. Please try again.');
        }
    };

    const clearFile = () => {
        setSelectedFile(null);
        setError('');
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <Authenticated
            header={
                <div className="flex items-center gap-4">
                    <Link href="/settings">
                        <Button variant="ghost" size="icon">
                            <CaretLeftIcon className="h-5 w-5" />
                        </Button>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Import Transactions
                    </h2>
                </div>
            }
        >
            <Head title="Import Transactions" />

            <div className="space-y-6">
                {/* Instructions Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>How to Import</CardTitle>
                        <CardDescription>
                            Import your transactions from CSV or Excel files
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="flex items-start gap-3 p-4 bg-muted rounded-lg">
                                <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground font-semibold text-sm">
                                    1
                                </div>
                                <div>
                                    <h4 className="font-medium">Download Template</h4>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Get the import template to ensure your data is formatted correctly
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3 p-4 bg-muted rounded-lg">
                                <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground font-semibold text-sm">
                                    2
                                </div>
                                <div>
                                    <h4 className="font-medium">Prepare Your Data</h4>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Fill in your transaction data following the template format
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-3 p-4 bg-muted rounded-lg">
                                <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground font-semibold text-sm">
                                    3
                                </div>
                                <div>
                                    <h4 className="font-medium">Upload File</h4>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Upload your file and we'll import your transactions
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Template Downloads */}
                <Card>
                    <CardHeader>
                        <CardTitle>Download Template</CardTitle>
                        <CardDescription>
                            Download a template file to get started with the correct format
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-4">
                            <Button
                                variant="outline"
                                onClick={() => handleDownloadTemplate('csv')}
                            >
                                <FileCsv className="mr-2 h-4 w-4" />
                                Download CSV Template
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => handleDownloadTemplate('excel')}
                            >
                                <FileXls className="mr-2 h-4 w-4" />
                                Download Excel Template
                            </Button>
                        </div>

                        <div className="mt-6 p-4 bg-muted rounded-lg">
                            <h4 className="font-medium mb-2">Required Columns</h4>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <div>
                                    <code className="bg-background px-2 py-1 rounded">Date</code>
                                    <span className="text-muted-foreground ml-2">Transaction date (YYYY-MM-DD)</span>
                                </div>
                                <div>
                                    <code className="bg-background px-2 py-1 rounded">Description</code>
                                    <span className="text-muted-foreground ml-2">Transaction description</span>
                                </div>
                                <div>
                                    <code className="bg-background px-2 py-1 rounded">Amount</code>
                                    <span className="text-muted-foreground ml-2">Positive for income, negative for expense</span>
                                </div>
                                <div>
                                    <code className="bg-background px-2 py-1 rounded">Category</code>
                                    <span className="text-muted-foreground ml-2">Category name (optional)</span>
                                </div>
                                <div>
                                    <code className="bg-background px-2 py-1 rounded">Brand</code>
                                    <span className="text-muted-foreground ml-2">Brand/Merchant name (optional)</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Upload Area */}
                <Card>
                    <CardHeader>
                        <CardTitle>Upload File</CardTitle>
                        <CardDescription>
                            Select or drag and drop your CSV or Excel file
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {error && (
                            <Alert variant="destructive">
                                <WarningCircleIcon className="h-4 w-4" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>{error}</AlertDescription>
                            </Alert>
                        )}

                        {result?.success && (
                            <Alert>
                                <CheckCircleIcon className="h-4 w-4" />
                                <AlertTitle>Success</AlertTitle>
                                <AlertDescription>{result.message}</AlertDescription>
                            </Alert>
                        )}

                        {!selectedFile ? (
                            <div
                                onClick={() => fileInputRef.current?.click()}
                                onDragOver={handleDragOver}
                                onDrop={handleDrop}
                                className="border-2 border-dashed border-muted-foreground/25 rounded-lg p-12 text-center cursor-pointer hover:border-muted-foreground/50 transition-colors"
                            >
                                <UploadIcon className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 className="mt-4 text-lg font-medium">
                                    Drop your file here, or click to browse
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Supports CSV, XLSX files up to 10MB
                                </p>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".csv,.xlsx,.xls"
                                    onChange={handleFileSelect}
                                    className="hidden"
                                />
                            </div>
                        ) : (
                            <div className="border rounded-lg p-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-4">
                                        {selectedFile.name.toLowerCase().endsWith('.csv') ? (
                                            <FileCsv className="h-10 w-10 text-green-500" />
                                        ) : (
                                            <FileXls className="h-10 w-10 text-blue-500" />
                                        )}
                                        <div>
                                            <p className="font-medium">{selectedFile.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatFileSize(selectedFile.size)}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={clearFile}
                                        disabled={isUploading}
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </Button>
                                </div>

                                {isUploading && (
                                    <div className="mt-4 space-y-2">
                                        <Progress value={uploadProgress} />
                                        <p className="text-sm text-muted-foreground text-center">
                                            Uploading...
                                        </p>
                                    </div>
                                )}

                                <div className="mt-4 flex justify-end gap-3">
                                    <Button
                                        variant="outline"
                                        onClick={clearFile}
                                        disabled={isUploading}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        onClick={handleUpload}
                                        disabled={isUploading}
                                    >
                                        {isUploading ? (
                                            'Uploading...'
                                        ) : (
                                            <>
                                                Import Transactions
                                                <ArrowRightIcon className="ml-2 h-4 w-4" />
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Results Table */}
                {result && result.errors.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-destructive">
                                Import Errors ({result.errors.length})
                            </CardTitle>
                            <CardDescription>
                                The following rows could not be imported
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-24">Row</TableHead>
                                        <TableHead>Error</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {result.errors.map((error, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="font-mono">
                                                {error.row}
                                            </TableCell>
                                            <TableCell className="text-destructive">
                                                {error.error}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </Authenticated>
    );
}
