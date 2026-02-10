import { Head, Link, useForm, router } from '@inertiajs/react';

import Authenticated from '@/Layouts/Authenticated';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    telegram_chat_id: string | null;
    telegram_username: string | null;
    telegram_verified_at: string | null;
}

interface Option {
    value: string;
    label: string;
}

interface Props {
    auth: { user: { name: string; email: string; role: string } };
    user: User;
    roles: Option[];
    statuses: Option[];
    flash?: { success?: string; error?: string };
}

export default function Edit({ auth, user, roles, statuses, flash }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        role: user.role,
        status: user.status,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.users.update', user.id));
    };

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
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">Edit User</h2>}>
            <Head title={`Edit ${user.name}`} />
            <div className="p-4 max-w-2xl mx-auto space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit User</CardTitle>
                        <CardDescription>Update user details for {user.name}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password">Password (leave blank to keep current)</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Enter new password"
                                />
                                {errors.password && <p className="text-sm text-destructive">{errors.password}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="role">Role</Label>
                                <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((r) => (
                                            <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.role && <p className="text-sm text-destructive">{errors.role}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status">Status</Label>
                                <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>{s.label}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-sm text-destructive">{errors.status}</p>}
                            </div>

                            <div className="flex gap-2 pt-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={route('admin.users.index')}>Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-medium">User Status</p>
                                <p className="text-sm text-muted-foreground">
                                    Currently: <Badge variant={user.status === 'active' ? 'default' : 'destructive'}>
                                        {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                                    </Badge>
                                </p>
                            </div>
                            <Button variant="outline" onClick={handleToggleStatus}>
                                {user.status === 'active' ? 'Deactivate' : 'Activate'}
                            </Button>
                        </div>

                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-medium">Telegram</p>
                                <p className="text-sm text-muted-foreground">
                                    {user.telegram_chat_id
                                        ? `Linked as @${user.telegram_username || 'unknown'}`
                                        : 'Not linked'}
                                </p>
                            </div>
                            {user.telegram_chat_id && (
                                <Button variant="destructive" size="sm" onClick={handleDisconnectTelegram}>
                                    Disconnect
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </Authenticated>
    );
}
