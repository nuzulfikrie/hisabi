import { useState, useRef, FormEvent } from 'react';
import { Head, router } from '@inertiajs/react';
import { Camera, Upload, X, Check, AlertCircle, Loader2, Receipt } from 'lucide-react';
import axios from 'axios';

import Authenticated from '@/Layouts/Authenticated';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { getAppCurrency, formatNumber } from '@/Utils';

interface ParsedData {
    merchant: string | null;
    amount: number | null;
    date: string | null;
    items: Array<{ description: string; price: number; quantity: number }>;
}

interface RawData {
    text: string;
    engine: string;
    word_count: number;
    char_count: number;
}

interface ScanResponse {
    success: boolean;
    data?: {
        parsed: ParsedData;
        raw: RawData;
    };
    message?: string;
}

export default function ScanReceipt({ auth }: { auth: any }) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [isScanning, setIsScanning] = useState(false);
    const [scanProgress, setScanProgress] = useState(0);
    const [error, setError] = useState<string | null>(null);
    const [parsedData, setParsedData] = useState<ParsedData | null>(null);
    const [rawData, setRawData] = useState<RawData | null>(null);
    const [editableData, setEditableData] = useState<{
        merchant: string;
        amount: string;
        date: string;
    }>({ merchant: '', amount: '', date: '' });
    const [isSaving, setIsSaving] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    const handleFile = (file: File) => {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        if (!validTypes.includes(file.type)) {
            setError('Please upload a valid image file (JPG, PNG, GIF, WEBP, or BMP)');
            return;
        }

        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            setError('File size must not exceed 10MB');
            return;
        }

        setSelectedFile(file);
        setPreviewUrl(URL.createObjectURL(file));
        setError(null);
        setParsedData(null);
        setRawData(null);
    };

    const handleDrop = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        const file = event.dataTransfer.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    const handleDragOver = (event: React.DragEvent<HTMLDivElement>) => {
        event.preventDefault();
    };

    const clearSelection = () => {
        setSelectedFile(null);
        setPreviewUrl(null);
        setParsedData(null);
        setRawData(null);
        setError(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleScan = async () => {
        if (!selectedFile) return;

        setIsScanning(true);
        setScanProgress(0);
        setError(null);

        // Simulate progress
        const progressInterval = setInterval(() => {
            setScanProgress(prev => Math.min(prev + 10, 90));
        }, 200);

        try {
            const formData = new FormData();
            formData.append('image', selectedFile);

            const response = await axios.post<ScanResponse>('/api/v1/ocr/scan-and-parse', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            clearInterval(progressInterval);
            setScanProgress(100);

            if (response.data.success && response.data.data) {
                setParsedData(response.data.data.parsed);
                setRawData(response.data.data.raw);
                setEditableData({
                    merchant: response.data.data.parsed.merchant || '',
                    amount: response.data.data.parsed.amount?.toString() || '',
                    date: response.data.data.parsed.date || new Date().toISOString().split('T')[0],
                });
            } else {
                setError(response.data.message || 'Failed to scan receipt');
            }
        } catch (err: any) {
            clearInterval(progressInterval);
            setScanProgress(0);

            if (err.response?.status === 429) {
                const retryAfter = err.response.data?.retry_after || 60;
                setError(`Rate limit exceeded. Please try again in ${retryAfter} seconds.`);
            } else if (err.response?.data?.message) {
                setError(err.response.data.message);
            } else {
                setError('An error occurred while scanning. Please try again.');
            }
        } finally {
            setIsScanning(false);
        }
    };

    const handleSaveTransaction = async () => {
        if (!editableData.merchant || !editableData.amount) {
            setError('Please fill in merchant and amount');
            return;
        }

        setIsSaving(true);
        setError(null);

        try {
            // First create or find the brand
            const brandResponse = await axios.post('/api/v1/brands', {
                name: editableData.merchant,
            });

            const brandId = brandResponse.data.brand.id;

            // Create the transaction
            await axios.post('/api/v1/transactions', {
                amount: parseFloat(editableData.amount),
                brand_id: brandId,
                created_at: editableData.date,
                note: parsedData?.items?.length
                    ? `Scanned from receipt: ${parsedData.items.length} items`
                    : 'Scanned from receipt',
            });

            // Redirect to transactions page
            router.visit('/transactions');
        } catch (err: any) {
            if (err.response?.data?.message) {
                setError(err.response.data.message);
            } else {
                setError('Failed to save transaction. Please try again.');
            }
            setIsSaving(false);
        }
    };

    const handleInputChange = (field: keyof typeof editableData, value: string) => {
        setEditableData(prev => ({ ...prev, [field]: value }));
    };

    return (
        <Authenticated
            auth={auth}
            header={
                <div className="flex items-center justify-between w-full">
                    <div className="flex items-center gap-2">
                        <Receipt className="h-6 w-6" />
                        <h2>Scan Receipt</h2>
                    </div>
                </div>
            }
        >
            <Head title="Scan Receipt" />

            <div className="p-4">
                <div className="max-w-4xl mx-auto grid gap-6">
                    {/* Upload Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Upload Receipt</CardTitle>
                            <CardDescription>
                                Take a photo or upload an image of your receipt
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {!previewUrl ? (
                                <div
                                    className="border-2 border-dashed border-muted-foreground/25 rounded-lg p-12 text-center cursor-pointer hover:border-muted-foreground/50 transition-colors"
                                    onClick={() => fileInputRef.current?.click()}
                                    onDrop={handleDrop}
                                    onDragOver={handleDragOver}
                                >
                                    <div className="flex flex-col items-center gap-4">
                                        <div className="p-4 bg-muted rounded-full">
                                            <Upload className="h-8 w-8 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <p className="font-medium">Click to upload or drag and drop</p>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                JPG, PNG, GIF, WEBP or BMP (max 10MB)
                                            </p>
                                        </div>
                                        <Button variant="outline" type="button">
                                            <Camera className="mr-2 h-4 w-4" />
                                            Select Image
                                        </Button>
                                    </div>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/bmp"
                                        className="hidden"
                                        onChange={handleFileSelect}
                                    />
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <div className="relative">
                                        <img
                                            src={previewUrl}
                                            alt="Receipt preview"
                                            className="max-h-96 mx-auto rounded-lg border"
                                        />
                                        <Button
                                            variant="destructive"
                                            size="icon"
                                            className="absolute top-2 right-2"
                                            onClick={clearSelection}
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>

                                    {isScanning ? (
                                        <div className="space-y-2">
                                            <div className="flex justify-between text-sm">
                                                <span>Scanning receipt...</span>
                                                <span>{scanProgress}%</span>
                                            </div>
                                            <Progress value={scanProgress} />
                                        </div>
                                    ) : (
                                        <Button
                                            onClick={handleScan}
                                            disabled={!selectedFile}
                                            className="w-full"
                                        >
                                            <Receipt className="mr-2 h-4 w-4" />
                                            Scan Receipt
                                        </Button>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Error Alert */}
                    {error && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {/* Parsed Results */}
                    {parsedData && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Parsed Receipt Data</CardTitle>
                                <CardDescription>
                                    Review and edit the extracted information before saving
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Editable Fields */}
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="merchant">Merchant</Label>
                                        <Input
                                            id="merchant"
                                            value={editableData.merchant}
                                            onChange={(e) => handleInputChange('merchant', e.target.value)}
                                            placeholder="Merchant name"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">Amount ({getAppCurrency()})</Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            step="0.01"
                                            value={editableData.amount}
                                            onChange={(e) => handleInputChange('amount', e.target.value)}
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="date">Date</Label>
                                        <Input
                                            id="date"
                                            type="date"
                                            value={editableData.date}
                                            onChange={(e) => handleInputChange('date', e.target.value)}
                                        />
                                    </div>
                                </div>

                                {/* Items Table */}
                                {parsedData.items.length > 0 && (
                                    <div>
                                        <Label className="mb-2 block">Items Detected</Label>
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Description</TableHead>
                                                    <TableHead className="text-right">Qty</TableHead>
                                                    <TableHead className="text-right">Price</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {parsedData.items.map((item, index) => (
                                                    <TableRow key={index}>
                                                        <TableCell>{item.description}</TableCell>
                                                        <TableCell className="text-right">{item.quantity}</TableCell>
                                                        <TableCell className="text-right">
                                                            {getAppCurrency()} {formatNumber(item.price, null)}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}

                                {/* Raw Text (Collapsible) */}
                                {rawData && (
                                    <div className="text-sm text-muted-foreground border-t pt-4">
                                        <p className="font-medium mb-1">OCR Engine: {rawData.engine}</p>
                                        <p>Words: {rawData.word_count} | Characters: {rawData.char_count}</p>
                                    </div>
                                )}

                                {/* Save Button */}
                                <Button
                                    onClick={handleSaveTransaction}
                                    disabled={isSaving}
                                    className="w-full"
                                    size="lg"
                                >
                                    {isSaving ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Saving...
                                        </>
                                    ) : (
                                        <>
                                            <Check className="mr-2 h-4 w-4" />
                                            Save as Transaction
                                        </>
                                    )}
                                </Button>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </Authenticated>
    );
}
