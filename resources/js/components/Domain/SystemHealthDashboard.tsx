import { useEffect, useState, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { getSystemHealth } from '@/Api';
import { format } from 'date-fns';
import {
    Database,
    HardDrive,
    Activity,
    AlertCircle,
    Users,
    CreditCard,
    Server,
    CheckCircle,
    XCircle,
    AlertTriangle,
} from 'lucide-react';

interface DatabaseStatus {
    status: string;
    message: string;
    driver: string;
    database: string | null;
}

interface StorageStatus {
    status: string;
    total: string;
    used: string;
    free: string;
    usage_percentage: number;
    message?: string;
}

interface QueueStatus {
    status: string;
    connection: string;
    pending_jobs: number | string;
    failed_jobs: number;
    message?: string;
}

interface UserStats {
    total: number;
    active_today: number;
}

interface TransactionStats {
    total_count: number;
    today_count: number;
    total_amount: number;
    today_amount: number;
}

interface SystemInfo {
    php_version: string;
    laravel_version: string;
    environment: string;
    debug_mode: boolean;
    timezone: string;
    cache_driver: string;
    session_driver: string;
}

interface HealthData {
    database: DatabaseStatus;
    storage: StorageStatus;
    queue: QueueStatus;
    errors: {
        count: number;
        errors: string[];
        message?: string;
    };
    users: UserStats;
    transactions: TransactionStats;
    system: SystemInfo;
}

function SystemHealthDashboard() {
    const [data, setData] = useState<HealthData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [lastUpdated, setLastUpdated] = useState<Date>(new Date());

    const fetchHealth = useCallback(async () => {
        try {
            const response = await getSystemHealth();
            setData(response.data);
            setLastUpdated(new Date());
            setError(null);
        } catch (err) {
            setError('Failed to fetch system health data');
            console.error(err);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchHealth();
        const interval = setInterval(fetchHealth, 60000); // Refresh every minute
        return () => clearInterval(interval);
    }, [fetchHealth]);

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'connected':
            case 'healthy':
                return <CheckCircle className="h-5 w-5 text-green-500" />;
            case 'warning':
                return <AlertTriangle className="h-5 w-5 text-yellow-500" />;
            case 'error':
                return <XCircle className="h-5 w-5 text-red-500" />;
            default:
                return <Activity className="h-5 w-5 text-gray-500" />;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'connected':
            case 'healthy':
                return <Badge className="bg-green-500">Healthy</Badge>;
            case 'warning':
                return <Badge className="bg-yellow-500">Warning</Badge>;
            case 'error':
                return <Badge className="bg-red-500">Error</Badge>;
            default:
                return <Badge variant="secondary">Unknown</Badge>;
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center h-64">
                <div className="text-muted-foreground">Loading system health data...</div>
            </div>
        );
    }

    if (error || !data) {
        return (
            <Card className="border-red-200">
                <CardContent className="pt-6">
                    <div className="flex items-center gap-2 text-red-600">
                        <AlertCircle className="h-5 w-5" />
                        <span>{error || 'Unable to load system health data'}</span>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {/* Last Updated */}
            <div className="flex justify-between items-center">
                <div className="text-sm text-muted-foreground">
                    Last updated: {format(lastUpdated, 'MMM d, yyyy HH:mm:ss')}
                </div>
                <button
                    onClick={fetchHealth}
                    className="text-sm text-blue-600 hover:text-blue-800"
                >
                    Refresh Now
                </button>
            </div>

            {/* Status Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* Database Card */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Database</CardTitle>
                        <Database className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between mb-2">
                            {getStatusIcon(data.database.status)}
                            {getStatusBadge(data.database.status)}
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {data.database.message}
                        </p>
                        <div className="mt-2 text-xs text-muted-foreground">
                            <div>Driver: {data.database.driver}</div>
                            {data.database.database && (
                                <div>Database: {data.database.database}</div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Storage Card */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Storage</CardTitle>
                        <HardDrive className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between mb-2">
                            {getStatusIcon(data.storage.status)}
                            {getStatusBadge(data.storage.status)}
                        </div>
                        <div className="space-y-2">
                            <Progress value={data.storage.usage_percentage} />
                            <div className="flex justify-between text-xs text-muted-foreground">
                                <span>{data.storage.used} used</span>
                                <span>{data.storage.total} total</span>
                            </div>
                            <div className="text-xs text-muted-foreground">
                                {data.storage.free} free ({data.storage.usage_percentage}% used)
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Queue Card */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Queue</CardTitle>
                        <Server className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center justify-between mb-2">
                            {getStatusIcon(data.queue.status)}
                            {getStatusBadge(data.queue.status)}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            <div>Connection: {data.queue.connection}</div>
                            <div>Pending jobs: {data.queue.pending_jobs}</div>
                            <div className={data.queue.failed_jobs > 0 ? 'text-red-500' : ''}>
                                Failed jobs: {data.queue.failed_jobs}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* User Stats */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">User Statistics</CardTitle>
                        <Users className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <div className="text-2xl font-bold">{data.users.total}</div>
                                <p className="text-xs text-muted-foreground">Total Users</p>
                            </div>
                            <div>
                                <div className="text-2xl font-bold">{data.users.active_today}</div>
                                <p className="text-xs text-muted-foreground">Active Today</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Transaction Stats */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Transaction Statistics</CardTitle>
                        <CreditCard className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <div className="text-2xl font-bold">{data.transactions.total_count.toLocaleString()}</div>
                                <p className="text-xs text-muted-foreground">Total Transactions</p>
                            </div>
                            <div>
                                <div className="text-2xl font-bold">{data.transactions.today_count}</div>
                                <p className="text-xs text-muted-foreground">Today's Transactions</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <div className="text-lg font-semibold">
                                    ${data.transactions.total_amount.toLocaleString()}
                                </div>
                                <p className="text-xs text-muted-foreground">Total Amount</p>
                            </div>
                            <div>
                                <div className="text-lg font-semibold">
                                    ${data.transactions.today_amount.toLocaleString()}
                                </div>
                                <p className="text-xs text-muted-foreground">Today's Amount</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* System Info */}
            <Card>
                <CardHeader>
                    <CardTitle>System Information</CardTitle>
                    <CardDescription>Environment and version details</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span className="text-muted-foreground">PHP Version:</span>{' '}
                            <span className="font-medium">{data.system.php_version}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground">Laravel Version:</span>{' '}
                            <span className="font-medium">{data.system.laravel_version}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground">Environment:</span>{' '}
                            <span className="font-medium">{data.system.environment}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground">Debug Mode:</span>{' '}
                            <span className={data.system.debug_mode ? 'text-yellow-600' : 'text-green-600'}>
                                {data.system.debug_mode ? 'Enabled' : 'Disabled'}
                            </span>
                        </div>
                        <div>
                            <span className="text-muted-foreground">Timezone:</span>{' '}
                            <span className="font-medium">{data.system.timezone}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground">Cache Driver:</span>{' '}
                            <span className="font-medium">{data.system.cache_driver}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground">Session Driver:</span>{' '}
                            <span className="font-medium">{data.system.session_driver}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Recent Errors */}
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Errors</CardTitle>
                            <CardDescription>Last 10 errors from the log</CardDescription>
                        </div>
                        <Badge variant={data.errors.count > 0 ? 'destructive' : 'secondary'}>
                            {data.errors.count} errors
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    {data.errors.errors.length === 0 ? (
                        <div className="flex items-center gap-2 text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            <span>No recent errors</span>
                        </div>
                    ) : (
                        <div className="space-y-2 max-h-64 overflow-y-auto">
                            {data.errors.errors.map((error, index) => (
                                <div
                                    key={index}
                                    className="p-3 bg-red-50 border border-red-200 rounded text-sm font-mono text-red-800 break-all"
                                >
                                    {error}
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

export default SystemHealthDashboard;
