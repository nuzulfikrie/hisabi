import { useEffect, useState, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { getAuditLogs, getAuditLogActions, getAuditLogEntityTypes } from '@/Api';
import { format } from 'date-fns';

interface AuditLog {
    id: string;
    user_id: number | null;
    action: string;
    entity_type: string;
    entity_id: string | null;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
}

interface Filters {
    userId: string;
    action: string;
    entityType: string;
    entityId: string;
    dateFrom: string;
    dateTo: string;
}

function AuditLogViewer() {
    const [logs, setLogs] = useState<AuditLog[]>([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [perPage] = useState(25);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    });
    const [filters, setFilters] = useState<Filters>({
        userId: '',
        action: '',
        entityType: '',
        entityId: '',
        dateFrom: '',
        dateTo: '',
    });
    const [actions, setActions] = useState<string[]>([]);
    const [entityTypes, setEntityTypes] = useState<string[]>([]);
    const [selectedLog, setSelectedLog] = useState<AuditLog | null>(null);
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const fetchLogs = useCallback(async () => {
        setLoading(true);
        try {
            const response = await getAuditLogs(page, perPage, {
                userId: filters.userId || undefined,
                action: filters.action || undefined,
                entityType: filters.entityType || undefined,
                entityId: filters.entityId || undefined,
                dateFrom: filters.dateFrom || undefined,
                dateTo: filters.dateTo || undefined,
            });
            setLogs(response.data);
            setMeta(response.meta);
        } catch (error) {
            console.error('Failed to fetch audit logs:', error);
        } finally {
            setLoading(false);
        }
    }, [page, perPage, filters]);

    const fetchFilterOptions = useCallback(async () => {
        try {
            const [actionsData, entityTypesData] = await Promise.all([
                getAuditLogActions(),
                getAuditLogEntityTypes(),
            ]);
            setActions(actionsData);
            setEntityTypes(entityTypesData);
        } catch (error) {
            console.error('Failed to fetch filter options:', error);
        }
    }, []);

    useEffect(() => {
        fetchLogs();
    }, [fetchLogs]);

    useEffect(() => {
        fetchFilterOptions();
    }, [fetchFilterOptions]);

    const handleFilterChange = (key: keyof Filters, value: string) => {
        setFilters((prev) => ({ ...prev, [key]: value }));
        setPage(1);
    };

    const clearFilters = () => {
        setFilters({
            userId: '',
            action: '',
            entityType: '',
            entityId: '',
            dateFrom: '',
            dateTo: '',
        });
        setPage(1);
    };

    const getActionBadgeColor = (action: string) => {
        switch (action) {
            case 'create':
                return 'bg-green-500';
            case 'update':
                return 'bg-blue-500';
            case 'delete':
                return 'bg-red-500';
            case 'login':
                return 'bg-purple-500';
            case 'logout':
                return 'bg-orange-500';
            default:
                return 'bg-gray-500';
        }
    };

    const renderDiff = (log: AuditLog) => {
        const oldValues = log.old_values || {};
        const newValues = log.new_values || {};
        const allKeys = Array.from(new Set([...Object.keys(oldValues), ...Object.keys(newValues)]));

        if (allKeys.length === 0) {
            return <p className="text-muted-foreground">No data changes</p>;
        }

        return (
            <div className="space-y-2">
                {allKeys.map((key) => {
                    const oldVal = oldValues[key];
                    const newVal = newValues[key];
                    const hasChanged = JSON.stringify(oldVal) !== JSON.stringify(newVal);

                    if (!hasChanged) return null;

                    return (
                        <div key={key} className="border rounded p-2">
                            <div className="font-semibold text-sm mb-1">{key}</div>
                            <div className="grid grid-cols-2 gap-2 text-sm">
                                {oldVal !== undefined && (
                                    <div className="bg-red-50 p-2 rounded">
                                        <div className="text-red-600 text-xs font-medium mb-1">Old</div>
                                        <div className="text-red-700 break-all">
                                            {typeof oldVal === 'object' ? JSON.stringify(oldVal) : String(oldVal)}
                                        </div>
                                    </div>
                                )}
                                {newVal !== undefined && (
                                    <div className="bg-green-50 p-2 rounded">
                                        <div className="text-green-600 text-xs font-medium mb-1">New</div>
                                        <div className="text-green-700 break-all">
                                            {typeof newVal === 'object' ? JSON.stringify(newVal) : String(newVal)}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    };

    return (
        <Card className="w-full">
            <CardHeader>
                <CardTitle>Audit Log Viewer</CardTitle>
            </CardHeader>
            <CardContent>
                {/* Filters */}
                <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                    <div>
                        <Label className="text-xs">Action</Label>
                        <Select
                            value={filters.action}
                            onValueChange={(value) => handleFilterChange('action', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="All actions" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All actions</SelectItem>
                                {actions.map((action) => (
                                    <SelectItem key={action} value={action}>
                                        {action}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label className="text-xs">Entity Type</Label>
                        <Select
                            value={filters.entityType}
                            onValueChange={(value) => handleFilterChange('entityType', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All types</SelectItem>
                                {entityTypes.map((type) => (
                                    <SelectItem key={type} value={type}>
                                        {type}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label className="text-xs">Entity ID</Label>
                        <Input
                            placeholder="Entity ID"
                            value={filters.entityId}
                            onChange={(e) => handleFilterChange('entityId', e.target.value)}
                        />
                    </div>

                    <div>
                        <Label className="text-xs">Date From</Label>
                        <Input
                            type="date"
                            value={filters.dateFrom}
                            onChange={(e) => handleFilterChange('dateFrom', e.target.value)}
                        />
                    </div>

                    <div>
                        <Label className="text-xs">Date To</Label>
                        <Input
                            type="date"
                            value={filters.dateTo}
                            onChange={(e) => handleFilterChange('dateTo', e.target.value)}
                        />
                    </div>

                    <div className="flex items-end">
                        <Button variant="outline" onClick={clearFilters} className="w-full">
                            Clear Filters
                        </Button>
                    </div>
                </div>

                {/* Table */}
                <div className="border rounded-md overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-muted">
                            <tr>
                                <th className="px-4 py-2 text-left">Time</th>
                                <th className="px-4 py-2 text-left">Action</th>
                                <th className="px-4 py-2 text-left">Entity</th>
                                <th className="px-4 py-2 text-left">User</th>
                                <th className="px-4 py-2 text-left">IP Address</th>
                                <th className="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center">
                                        Loading...
                                    </td>
                                </tr>
                            ) : logs.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        No audit logs found
                                    </td>
                                </tr>
                            ) : (
                                logs.map((log) => (
                                    <tr key={log.id} className="border-t hover:bg-muted/50">
                                        <td className="px-4 py-2">
                                            {format(new Date(log.created_at), 'MMM d, yyyy HH:mm')}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Badge className={getActionBadgeColor(log.action)}>
                                                {log.action}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-2">
                                            {log.entity_type}
                                            {log.entity_id && (
                                                <span className="text-muted-foreground ml-1">
                                                    #{log.entity_id}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2">
                                            {log.user?.name || 'System'}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground">
                                            {log.ip_address || '-'}
                                        </td>
                                        <td className="px-4 py-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    setSelectedLog(log);
                                                    setIsDialogOpen(true);
                                                }}
                                            >
                                                View
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="flex items-center justify-between mt-4">
                    <div className="text-sm text-muted-foreground">
                        Showing {(meta.current_page - 1) * meta.per_page + 1} to{' '}
                        {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} entries
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                            disabled={page === 1 || loading}
                        >
                            Previous
                        </Button>
                        <span className="px-3 py-1 text-sm">
                            Page {meta.current_page} of {meta.last_page}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
                            disabled={page === meta.last_page || loading}
                        >
                            Next
                        </Button>
                    </div>
                </div>

                {/* Detail Dialog */}
                <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                    <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Audit Log Details</DialogTitle>
                        </DialogHeader>
                        {selectedLog && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="text-muted-foreground">ID:</span>{' '}
                                        {selectedLog.id}
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Action:</span>{' '}
                                        <Badge className={getActionBadgeColor(selectedLog.action)}>
                                            {selectedLog.action}
                                        </Badge>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Entity Type:</span>{' '}
                                        {selectedLog.entity_type}
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Entity ID:</span>{' '}
                                        {selectedLog.entity_id || '-'}
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">User:</span>{' '}
                                        {selectedLog.user?.name || 'System'} ({selectedLog.user?.email || 'N/A'})
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Time:</span>{' '}
                                        {format(new Date(selectedLog.created_at), 'MMM d, yyyy HH:mm:ss')}
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">IP Address:</span>{' '}
                                        {selectedLog.ip_address || '-'}
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">User Agent:</span>{' '}
                                        {selectedLog.user_agent || '-'}
                                    </div>
                                </div>

                                <div className="border-t pt-4">
                                    <h4 className="font-semibold mb-2">Changes</h4>
                                    {renderDiff(selectedLog)}
                                </div>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    );
}

export default AuditLogViewer;
