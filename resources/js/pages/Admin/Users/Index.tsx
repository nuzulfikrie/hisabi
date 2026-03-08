import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

import Authenticated from '@/Layouts/Authenticated';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    telegram_chat_id: string | null;
    telegram_username: string | null;
    last_login_at: string | null;
    created_at: string;
}

interface PaginatedData {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Option {
    value: string;
    label: string;
}

interface Props {
    auth: { user: { name: string; email: string; role: string } };
    users: PaginatedData;
    filters: { name?: string; email?: string; status?: string; role?: string };
    statuses: Option[];
    roles: Option[];
    flash?: { success?: string; error?: string };
}

const statusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status) {
        case 'active': return 'default';
        case 'inactive': return 'destructive';
        case 'suspended': return 'secondary';
        default: return 'outline';
    }
};

const roleBadgeVariant = (role: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (role) {
        case 'admin': return 'destructive';
        case 'accountant': return 'secondary';
        default: return 'outline';
    }
};

export default function Index({ auth, users, filters, statuses, roles, flash }: Props) {
    const [nameFilter, setNameFilter] = useState(filters.name || '');
    const [emailFilter, setEmailFilter] = useState(filters.email || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || '');

    const applyFilters = () => {
        router.get(route('admin.users.index'), {
            name: nameFilter || undefined,
            email: emailFilter || undefined,
            status: statusFilter || undefined,
            role: roleFilter || undefined,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setNameFilter('');
        setEmailFilter('');
        setStatusFilter('');
        setRoleFilter('');
        router.get(route('admin.users.index'));
    };

    const columns: ColumnDef<User>[] = [
        {
            accessorKey: 'name',
            header: 'Name',
        },
        {
            accessorKey: 'email',
            header: 'Email',
        },
        {
            accessorKey: 'role',
            header: 'Role',
            cell: ({ row }) => (
                <Badge variant={roleBadgeVariant(row.getValue('role'))}>
                    {(row.getValue('role') as string).charAt(0).toUpperCase() + (row.getValue('role') as string).slice(1)}
                </Badge>
            ),
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <Badge variant={statusBadgeVariant(row.getValue('status'))}>
                    {(row.getValue('status') as string).charAt(0).toUpperCase() + (row.getValue('status') as string).slice(1)}
                </Badge>
            ),
        },
        {
            id: 'telegram',
            header: 'Telegram',
            cell: ({ row }) => (
                <span className={row.original.telegram_chat_id ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'}>
                    {row.original.telegram_chat_id ? `@${row.original.telegram_username || 'Linked'}` : 'Not linked'}
                </span>
            ),
        },
        {
            accessorKey: 'last_login_at',
            header: 'Last Login',
            cell: ({ row }) => row.getValue('last_login_at')
                ? new Date(row.getValue('last_login_at') as string).toLocaleDateString()
                : 'Never',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">Actions</Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={route('admin.users.show', row.original.id)}>View</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={route('admin.users.edit', row.original.id)}>Edit</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => {
                                if (confirm('Are you sure you want to delete this user?')) {
                                    router.delete(route('admin.users.destroy', row.original.id));
                                }
                            }}
                            className="text-destructive"
                        >
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">User Management</h2>}>
            <Head title="Users" />
            <div className="p-4 max-w-7xl mx-auto space-y-4">
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
                    <h1 className="text-2xl font-bold">Users</h1>
                    <Button asChild>
                        <Link href={route('admin.users.create')}>Create User</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-2 items-end">
                    <Input
                        placeholder="Filter by name..."
                        value={nameFilter}
                        onChange={(e) => setNameFilter(e.target.value)}
                        className="max-w-[200px]"
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                    />
                    <Input
                        placeholder="Filter by email..."
                        value={emailFilter}
                        onChange={(e) => setEmailFilter(e.target.value)}
                        className="max-w-[200px]"
                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                    />
                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            {statuses.map((s) => (
                                <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={roleFilter} onValueChange={setRoleFilter}>
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Role" />
                        </SelectTrigger>
                        <SelectContent>
                            {roles.map((r) => (
                                <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button onClick={applyFilters} variant="secondary">Filter</Button>
                    <Button onClick={clearFilters} variant="ghost">Clear</Button>
                </div>

                <DataTable columns={columns} data={users.data} searchColumn="name" searchPlaceholder="Search users..." pageSize={20} />

                {users.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {users.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </Authenticated>
    );
}
