import { Head, Link, router } from '@inertiajs/react';

import Authenticated from '@/Layouts/Authenticated';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface TelegramTransaction {
    id: number;
    amount: number;
    description: string;
    status: string;
    created_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    locale: string;
    timezone: string | null;
    phone: string | null;
    telegram_chat_id: string | null;
    telegram_username: string | null;
    telegram_verified_at: string | null;
    last_login_at: string | null;
    created_at: string;
    email_verified_at: string | null;
}

interface Props {
    auth: { user: { name: string; email: string; role: string } };
    user: User;
    telegramTransactions: TelegramTransaction[];
    flash?: { success?: string; error?: string };
}

export default function Show({ auth, user, telegramTransactions, flash }: Props) {
    const handleToggleStatus = () => {
        if (confirm(`Are you sure you want to ${user.status === 'active' ? 'deactivate' : 'activate'} this user?`)) {
            router.post(route('admin.users.toggle-status', user.id));
        }
    };

    const handleDisconnectTelegram = () => {
        if (confirm('Are you sure you want to disconnect this user\'s Telegram account?')) {
            router.post(route('admin.users.disconnect-telegram', user.id));
        }
    };

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">User Details</h2>}>
            <Head title={user.name} />
            <div className="p-4 max-w-4xl mx-auto space-y-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">{user.name}</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('admin.users.edit', user.id)}>Edit</Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('admin.users.index')}>Back to Users</Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Email</span>
                                <span>{user.email}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Role</span>
                                <Badge variant={user.role === 'admin' ? 'destructive' : 'outline'}>
                                    {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                                </Badge>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-muted-foreground">Status</span>
                                <div className="flex items-center gap-2">
                                    <Badge variant={user.status === 'active' ? 'default' : 'destructive'}>
                                        {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                                    </Badge>
                                    <Button variant="ghost" size="sm" onClick={handleToggleStatus}>
                                        {user.status === 'active' ? 'Deactivate' : 'Activate'}
                                    </Button>
                                </div>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Locale</span>
                                <span>{user.locale || 'en'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Timezone</span>
                                <span>{user.timezone || 'Not set'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Phone</span>
                                <span>{user.phone || 'Not set'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Email Verified</span>
                                <span>{user.email_verified_at ? new Date(user.email_verified_at).toLocaleDateString() : 'Not verified'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Last Login</span>
                                <span>{user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Created</span>
                                <span>{new Date(user.created_at).toLocaleDateString()}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Telegram</CardTitle>
                            <CardDescription>
                                {user.telegram_chat_id ? 'Account is linked' : 'No Telegram account linked'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {user.telegram_chat_id ? (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Username</span>
                                        <span>@{user.telegram_username || 'N/A'}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Chat ID</span>
                                        <span className="font-mono text-sm">{user.telegram_chat_id}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Verified</span>
                                        <span>{user.telegram_verified_at ? new Date(user.telegram_verified_at).toLocaleString() : 'Not verified'}</span>
                                    </div>
                                    <Button variant="destructive" size="sm" onClick={handleDisconnectTelegram} className="w-full mt-2">
                                        Disconnect Telegram
                                    </Button>
                                </>
                            ) : (
                                <p className="text-muted-foreground text-center py-4">
                                    This user has not linked a Telegram account.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {telegramTransactions.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Telegram Transactions</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {telegramTransactions.map((tx) => (
                                        <TableRow key={tx.id}>
                                            <TableCell>{tx.description}</TableCell>
                                            <TableCell>{tx.amount}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{tx.status}</Badge>
                                            </TableCell>
                                            <TableCell>{new Date(tx.created_at).toLocaleDateString()}</TableCell>
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
