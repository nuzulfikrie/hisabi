import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import {
    TelegramLogoIcon,
    CheckCircleIcon,
    WarningCircleIcon,
    CopyIcon,
    ClockIcon,
    ChatTeardropTextIcon,
    UnlinkIcon,
} from "@phosphor-icons/react";

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import Authenticated from "@/Layouts/Authenticated";

interface TelegramTransaction {
    id: number;
    raw_message: string;
    status: string;
    status_label: string;
    status_badge: string;
    created_at: string;
    processed_at: string | null;
}

interface TelegramProps {
    isLinked: boolean;
    telegramUsername: string | null;
    telegramChatId: string | null;
    telegramVerifiedAt: string | null;
    verificationCode: string | null;
    recentTransactions: TelegramTransaction[];
    rateLimit: {
        attempts: number;
        available_in: number;
    };
    flash?: {
        success?: string;
        error?: string;
        verification_code?: string;
    };
}

export default function Telegram() {
    const { props } = usePage();
    const {
        isLinked,
        telegramUsername,
        telegramChatId,
        telegramVerifiedAt,
        verificationCode,
        recentTransactions,
        rateLimit,
        flash,
    } = props as unknown as TelegramProps;

    const [showUnlinkDialog, setShowUnlinkDialog] = useState(false);
    const [copied, setCopied] = useState(false);

    const generateCodeForm = useForm({});
    const unlinkForm = useForm({});

    const handleGenerateCode = () => {
        generateCodeForm.post(route('settings.telegram.generate-code'));
    };

    const handleUnlink = () => {
        unlinkForm.post(route('settings.telegram.unlink'), {
            onSuccess: () => setShowUnlinkDialog(false),
        });
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const getStatusBadgeVariant = (badge: string): "default" | "secondary" | "destructive" | "outline" => {
        switch (badge) {
            case 'success':
                return 'default';
            case 'danger':
                return 'destructive';
            case 'warning':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const isRateLimited = rateLimit.attempts >= 3;
    const rateLimitMinutes = Math.ceil(rateLimit.available_in / 60);

    return (
        <Authenticated
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Telegram Settings</h2>}
        >
            <Head title="Telegram Settings" />

            <div className="space-y-6">
                {/* Status Card */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                    <TelegramLogoIcon className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <CardTitle>Telegram Bot</CardTitle>
                                    <CardDescription>
                                        Connect your Telegram account to record transactions via chat
                                    </CardDescription>
                                </div>
                            </div>
                            <Badge variant={isLinked ? "default" : "destructive"} className="text-sm">
                                {isLinked ? (
                                    <span className="flex items-center gap-1">
                                        <CheckCircleIcon className="w-4 h-4" />
                                        Linked
                                    </span>
                                ) : (
                                    <span className="flex items-center gap-1">
                                        <WarningCircleIcon className="w-4 h-4" />
                                        Not Linked
                                    </span>
                                )}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* Flash Messages */}
                        {flash?.success && (
                            <Alert>
                                <CheckCircleIcon className="w-4 h-4" />
                                <AlertTitle>Success</AlertTitle>
                                <AlertDescription>{flash.success}</AlertDescription>
                            </Alert>
                        )}
                        {flash?.error && (
                            <Alert variant="destructive">
                                <WarningCircleIcon className="w-4 h-4" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>{flash.error}</AlertDescription>
                            </Alert>
                        )}

                        {isLinked ? (
                            /* Linked State */
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="p-4 bg-muted rounded-lg">
                                        <p className="text-sm text-muted-foreground">Telegram Username</p>
                                        <p className="text-lg font-medium">@{telegramUsername}</p>
                                    </div>
                                    <div className="p-4 bg-muted rounded-lg">
                                        <p className="text-sm text-muted-foreground">Chat ID</p>
                                        <p className="text-lg font-medium font-mono">{telegramChatId}</p>
                                    </div>
                                </div>
                                <div className="p-4 bg-muted rounded-lg">
                                    <p className="text-sm text-muted-foreground">Linked Since</p>
                                    <p className="text-lg font-medium">{telegramVerifiedAt}</p>
                                </div>

                                <Dialog open={showUnlinkDialog} onOpenChange={setShowUnlinkDialog}>
                                    <DialogTrigger asChild>
                                        <Button variant="destructive" className="w-full sm:w-auto">
                                            <UnlinkIcon className="w-4 h-4 mr-2" />
                                            Unlink Telegram Account
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Unlink Telegram Account</DialogTitle>
                                            <DialogDescription>
                                                Are you sure you want to unlink your Telegram account? You will no longer be able to record transactions via Telegram bot.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <Button variant="outline" onClick={() => setShowUnlinkDialog(false)}>
                                                Cancel
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                onClick={handleUnlink}
                                                disabled={unlinkForm.processing}
                                            >
                                                {unlinkForm.processing ? 'Unlinking...' : 'Unlink Account'}
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        ) : (
                            /* Not Linked State */
                            <div className="space-y-4">
                                <div className="p-4 bg-muted rounded-lg">
                                    <h4 className="font-medium mb-2">How to link your account:</h4>
                                    <ol className="list-decimal list-inside space-y-2 text-sm text-muted-foreground">
                                        <li>Generate a verification code below</li>
                                        <li>Open Telegram and search for <strong>@HisabiBot</strong></li>
                                        <li>Send the command: <code className="bg-background px-1 py-0.5 rounded">/link &lt;CODE&gt;</code></li>
                                        <li>Your account will be automatically linked!</li>
                                    </ol>
                                </div>

                                {/* Rate Limit Warning */}
                                {isRateLimited && (
                                    <Alert variant="destructive">
                                        <ClockIcon className="w-4 h-4" />
                                        <AlertTitle>Rate Limited</AlertTitle>
                                        <AlertDescription>
                                            You have exceeded the maximum number of attempts. Please try again in {rateLimitMinutes} minutes.
                                        </AlertDescription>
                                    </Alert>
                                )}

                                {/* Verification Code Display */}
                                {(flash?.verification_code || verificationCode) && !isRateLimited && (
                                    <div className="p-6 bg-blue-50 dark:bg-blue-950 border-2 border-blue-200 dark:border-blue-800 rounded-lg text-center">
                                        <p className="text-sm text-blue-600 dark:text-blue-400 mb-2">Your Verification Code</p>
                                        <div className="flex items-center justify-center gap-3">
                                            <code className="text-4xl font-bold font-mono text-blue-700 dark:text-blue-300 tracking-wider">
                                                {flash?.verification_code || verificationCode}
                                            </code>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => copyToClipboard(flash?.verification_code || verificationCode || '')}
                                            >
                                                <CopyIcon className="w-4 h-4" />
                                            </Button>
                                        </div>
                                        {copied && (
                                            <p className="text-xs text-green-600 mt-2">Copied to clipboard!</p>
                                        )}
                                        <p className="text-xs text-muted-foreground mt-3">
                                            This code will expire in 10 minutes
                                        </p>
                                    </div>
                                )}

                                <Button
                                    onClick={handleGenerateCode}
                                    disabled={generateCodeForm.processing || isRateLimited}
                                    className="w-full sm:w-auto"
                                >
                                    <ChatTeardropTextIcon className="w-4 h-4 mr-2" />
                                    {generateCodeForm.processing ? 'Generating...' : 'Generate Verification Code'}
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Recent Transactions */}
                {isLinked && recentTransactions.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Telegram Transactions</CardTitle>
                            <CardDescription>
                                Last 10 transactions recorded via Telegram
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Message</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentTransactions.map((transaction) => (
                                        <TableRow key={transaction.id}>
                                            <TableCell className="max-w-xs truncate">
                                                {transaction.raw_message}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getStatusBadgeVariant(transaction.status_badge)}>
                                                    {transaction.status_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {transaction.created_at}
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
