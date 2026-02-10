import { useState } from 'react';
import { Head, router } from '@inertiajs/react';

import Authenticated from '@/Layouts/Authenticated';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Switch } from '@/components/ui/switch';

interface Setting {
    id: number;
    key: string;
    name: string;
    value: string;
    type: string;
    group: string;
    description: string | null;
}

interface Props {
    auth: { user: { name: string; email: string; role: string } };
    settings: Setting[];
    groups: string[];
    currentGroup: string;
    flash?: { success?: string; error?: string };
}

export default function Index({ auth, settings, groups, currentGroup, flash }: Props) {
    const [values, setValues] = useState<Record<string, string>>(
        settings.reduce((acc, s) => ({ ...acc, [s.key]: s.value }), {} as Record<string, string>)
    );
    const [saving, setSaving] = useState(false);

    const handleChange = (key: string, value: string) => {
        setValues((prev) => ({ ...prev, [key]: value }));
    };

    const handleSave = () => {
        setSaving(true);
        router.post(route('admin.settings.update'), { settings: values }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const handleGroupChange = (group: string) => {
        router.get(route('admin.settings.index'), { group }, { preserveState: true });
    };

    const renderSettingInput = (setting: Setting) => {
        const value = values[setting.key] ?? setting.value;

        switch (setting.type) {
            case 'boolean':
                return (
                    <Switch
                        checked={value === '1' || value === 'true'}
                        onCheckedChange={(checked) => handleChange(setting.key, checked ? '1' : '0')}
                    />
                );
            case 'number':
            case 'integer':
                return (
                    <Input
                        type="number"
                        value={value}
                        onChange={(e) => handleChange(setting.key, e.target.value)}
                        className="max-w-xs"
                    />
                );
            default:
                return (
                    <Input
                        value={value}
                        onChange={(e) => handleChange(setting.key, e.target.value)}
                        className="max-w-md"
                    />
                );
        }
    };

    return (
        <Authenticated auth={auth} header={<h2 className="text-lg font-semibold">System Settings</h2>}>
            <Head title="Settings" />
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
                    <h1 className="text-2xl font-bold">System Settings</h1>
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? 'Saving...' : 'Save Settings'}
                    </Button>
                </div>

                {groups.length > 0 ? (
                    <Tabs value={currentGroup} onValueChange={handleGroupChange}>
                        <TabsList>
                            {groups.map((group) => (
                                <TabsTrigger key={group} value={group} className="capitalize">
                                    {group}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                        {groups.map((group) => (
                            <TabsContent key={group} value={group}>
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="capitalize">{group} Settings</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        {settings
                                            .filter((s) => s.group === group)
                                            .map((setting) => (
                                                <div key={setting.key} className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div className="space-y-1">
                                                        <Label htmlFor={setting.key}>{setting.name}</Label>
                                                        {setting.description && (
                                                            <p className="text-sm text-muted-foreground">{setting.description}</p>
                                                        )}
                                                    </div>
                                                    {renderSettingInput(setting)}
                                                </div>
                                            ))}
                                        {settings.filter((s) => s.group === group).length === 0 && (
                                            <p className="text-muted-foreground text-center py-4">
                                                No settings in this group.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        ))}
                    </Tabs>
                ) : (
                    <Card>
                        <CardContent className="py-8">
                            <p className="text-muted-foreground text-center">
                                No settings have been configured yet.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </Authenticated>
    );
}
