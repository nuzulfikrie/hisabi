import { Head, router } from '@inertiajs/react';

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
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Session {
    id: string;
    ip_address: string;
    user_agent: string;
    last_activity: string;
    is_current: boolean;
}

interface Props {
    auth: { user: { name: string; email: string; role: string } };
    sessions: Session[];
    flash?: { success?: string; error?: string };
}

function parseBrowser(userAgent: string): string {
    if (!userAgent) {
        return 'Unknown';
    }

    if (userAgent.includes('Firefox')) {
        return 'Firefox';
    }
    if (userAgent.includes('Edg')) {
        return 'Edge';
    }
    if (userAgent.includes('Chrome')) {
        return 'Chrome';
    }
    if (userAgent.includes('Safari')) {
        return 'Safari';
    }

    return 'Other';
}

function parseOS(userAgent: string): string {
    if (!userAgent) {
        return 'Unknown';
    }

    if (userAgent.includes('Windows')) {
        return 'Windows';
    }
    if (userAgent.includes('Mac OS')) {
        return 'macOS';
    }
    if (userAgent.includes('Linux')) {
        return 'Linux';
    }
    if (userAgent.includes('Android')) {
        return 'Android';
    }
    if (userAgent.includes('iPhone') || userAgent.includes('iPad')) {
        return 'iOS';
    }

    return 'Other';
}

export default function Index({ auth, sessions, flash }: Props) {
    const handleTerminate = (sessionId: string) => {
        if (confirm('Are you sure you want to terminate this session?')) {
            router.delete(route('sessions.destroy', sessionId));
        }
    };

    const handleTerminateAll = () => {
        if (confirm('Are you sure you want to terminate all other sessions?')) {
            router.delete(route('sessions.destroy-all'));
        }
    };

    const otherSessions = sessions.filter((s) => !s.is_current);

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">Session Management</h2>}>
            <Head title="Sessions" />
            <div className="p-4 max-w-4xl mx-auto space-y-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}
                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Active Sessions</h1>
                    {otherSessions.length > 0 && (
                        <Button variant="destructive" onClick={handleTerminateAll}>
                            Terminate All Others
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Your Sessions</CardTitle>
                        <CardDescription>
                            Manage your active sessions across different devices and browsers.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Browser / OS</TableHead>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead>Last Activity</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sessions.map((session) => (
                                    <TableRow key={session.id}>
                                        <TableCell>
                                            <div>
                                                <p className="font-medium">{parseBrowser(session.user_agent)}</p>
                                                <p className="text-sm text-muted-foreground">{parseOS(session.user_agent)}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell className="font-mono text-sm">{session.ip_address}</TableCell>
                                        <TableCell>{session.last_activity}</TableCell>
                                        <TableCell>
                                            {session.is_current ? (
                                                <Badge variant="default">Current</Badge>
                                            ) : (
                                                <Badge variant="outline">Active</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {!session.is_current && (
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => handleTerminate(session.id)}
                                                >
                                                    Terminate
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {sessions.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground h-24">
                                            No active sessions found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </Authenticated>
    );
}
