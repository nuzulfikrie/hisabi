import { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    UserCircleIcon,
    CaretLeftIcon,
    CaretDownIcon,
    SlidersHorizontalIcon,
    KeyIcon,
    DownloadIcon,
    UploadIcon,
    TagIcon,
    FunnelIcon,
    BellRingingIcon,
    ChatCircleDotsIcon,
    SignOutIcon,
    CopyIcon,
    TrashIcon,
    PlusIcon,
    CheckIcon,
    PlayIcon
} from "@phosphor-icons/react";

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import ApplicationLogo from "@/components/Global/ApplicationLogo";
import { updateUserProfile } from '@/Api/user';
import { getUserPreferences, updateUserPreferences } from '@/Api/settings';
import { getApiKeys, createApiKey, deleteApiKey } from '@/Api/apiKeys';
import { getSmsParserRules, createSmsParserRule, updateSmsParserRule, deleteSmsParserRule, testSmsParserRule } from '@/Api/smsParser';

// Helper function for route generation
const route = (name: string) => {
    const routes: Record<string, string> = {
        'dashboard': '/dashboard',
        'logout': '/logout'
    };
    return routes[name] || '/';
};

interface User {
    id: number;
    name: string;
    email: string;
}

interface Preferences {
    uuid: string;
    currency: string;
    date_format: string;
    theme: string;
    language: string;
    default_transaction_type: string;
    email_notifications: boolean;
    push_notifications: boolean;
}

interface ApiKey {
    uuid: string;
    name: string;
    key: string;
    created_at: string;
    last_used_at: string | null;
}

interface SmsParserRule {
    uuid: string;
    name: string;
    pattern: string;
    bank_name: string;
    is_active: boolean;
    created_at: string;
}

const settingsNavItems = [
    {
        section: "General",
        items: [
            {
                title: "Account",
                value: "account",
                icon: UserCircleIcon,
            },
            {
                title: "Preferences",
                value: "preferences",
                icon: SlidersHorizontalIcon,
            },
            {
                title: "API Key",
                value: "api-key",
                icon: KeyIcon,
            },
            {
                title: "Import",
                value: "import",
                icon: DownloadIcon,
            },
            {
                title: "Export",
                value: "export",
                icon: UploadIcon,
            },
        ]
    },
    {
        section: "Transactions",
        items: [
            {
                title: "Tags",
                value: "tags",
                icon: TagIcon,
            },
            {
                title: "SMS Parser Rules",
                value: "sms-parser-rules",
                icon: FunnelIcon,
            },
        ]
    },
    {
        section: "More",
        items: [
            {
                title: "Product Updates",
                value: "product-updates",
                icon: BellRingingIcon,
            },
            {
                title: "Feedback",
                value: "feedback",
                icon: ChatCircleDotsIcon,
            },
        ]
    },
];

// Currency options
const currencies = [
    { value: 'USD', label: 'USD ($) - US Dollar' },
    { value: 'MYR', label: 'MYR (RM) - Malaysian Ringgit' },
    { value: 'SGD', label: 'SGD (S$) - Singapore Dollar' },
    { value: 'EUR', label: 'EUR (€) - Euro' },
    { value: 'GBP', label: 'GBP (£) - British Pound' },
    { value: 'JPY', label: 'JPY (¥) - Japanese Yen' },
    { value: 'AUD', label: 'AUD (A$) - Australian Dollar' },
    { value: 'CAD', label: 'CAD (C$) - Canadian Dollar' },
    { value: 'IDR', label: 'IDR (Rp) - Indonesian Rupiah' },
    { value: 'THB', label: 'THB (฿) - Thai Baht' },
    { value: 'PHP', label: 'PHP (₱) - Philippine Peso' },
    { value: 'INR', label: 'INR (₹) - Indian Rupee' },
    { value: 'CNY', label: 'CNY (¥) - Chinese Yuan' },
    { value: 'KRW', label: 'KRW (₩) - South Korean Won' },
    { value: 'VND', label: 'VND (₫) - Vietnamese Dong' },
];

const dateFormats = [
    { value: 'DD/MM/YYYY', label: 'DD/MM/YYYY (31/12/2024)' },
    { value: 'MM/DD/YYYY', label: 'MM/DD/YYYY (12/31/2024)' },
    { value: 'YYYY-MM-DD', label: 'YYYY-MM-DD (2024-12-31)' },
    { value: 'DD-MM-YYYY', label: 'DD-MM-YYYY (31-12-2024)' },
];

const themes = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'system', label: 'System' },
];

const languages = [
    { value: 'en', label: 'English' },
    { value: 'ms', label: 'Malay' },
    { value: 'zh', label: 'Chinese' },
    { value: 'ja', label: 'Japanese' },
    { value: 'ko', label: 'Korean' },
];

const transactionTypes = [
    { value: 'expense', label: 'Expense' },
    { value: 'income', label: 'Income' },
];

// Preferences Component
function PreferencesSection() {
    const [preferences, setPreferences] = useState<Preferences | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        loadPreferences();
    }, []);

    useEffect(() => {
        if (message || error) {
            const timer = setTimeout(() => {
                setMessage('');
                setError('');
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [message, error]);

    const loadPreferences = async () => {
        try {
            const { data } = await getUserPreferences();
            setPreferences(data.preferences);
        } catch (err: any) {
            setError(err.message || 'Failed to load preferences');
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async () => {
        if (!preferences) return;
        setSaving(true);
        setError('');
        setMessage('');

        try {
            await updateUserPreferences({
                currency: preferences.currency,
                date_format: preferences.date_format,
                theme: preferences.theme,
                language: preferences.language,
                default_transaction_type: preferences.default_transaction_type,
                email_notifications: preferences.email_notifications,
                push_notifications: preferences.push_notifications,
            });
            setMessage('Preferences saved successfully');
        } catch (err: any) {
            setError(err.message || 'Failed to save preferences');
        } finally {
            setSaving(false);
        }
    };

    const updatePreference = <K extends keyof Preferences>(key: K, value: Preferences[K]) => {
        if (preferences) {
            setPreferences({ ...preferences, [key]: value });
        }
    };

    if (loading) {
        return (
            <Card>
                <CardContent className="pt-6">
                    <div className="text-center text-muted-foreground">Loading preferences...</div>
                </CardContent>
            </Card>
        );
    }

    if (!preferences) {
        return (
            <Card>
                <CardContent className="pt-6">
                    <div className="text-center text-destructive">Failed to load preferences</div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Preferences</CardTitle>
                <CardDescription>Customize your application experience</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {message && (
                    <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                        {message}
                    </div>
                )}

                {error && (
                    <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {error}
                    </div>
                )}

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Currency */}
                    <div className="space-y-2">
                        <Label htmlFor="currency">Currency</Label>
                        <Select
                            value={preferences.currency}
                            onValueChange={(value) => updatePreference('currency', value)}
                        >
                            <SelectTrigger id="currency">
                                <SelectValue placeholder="Select currency" />
                            </SelectTrigger>
                            <SelectContent>
                                {currencies.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Date Format */}
                    <div className="space-y-2">
                        <Label htmlFor="date_format">Date Format</Label>
                        <Select
                            value={preferences.date_format}
                            onValueChange={(value) => updatePreference('date_format', value)}
                        >
                            <SelectTrigger id="date_format">
                                <SelectValue placeholder="Select date format" />
                            </SelectTrigger>
                            <SelectContent>
                                {dateFormats.map((f) => (
                                    <SelectItem key={f.value} value={f.value}>
                                        {f.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Theme */}
                    <div className="space-y-2">
                        <Label htmlFor="theme">Theme</Label>
                        <Select
                            value={preferences.theme}
                            onValueChange={(value) => updatePreference('theme', value)}
                        >
                            <SelectTrigger id="theme">
                                <SelectValue placeholder="Select theme" />
                            </SelectTrigger>
                            <SelectContent>
                                {themes.map((t) => (
                                    <SelectItem key={t.value} value={t.value}>
                                        {t.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Language */}
                    <div className="space-y-2">
                        <Label htmlFor="language">Language</Label>
                        <Select
                            value={preferences.language}
                            onValueChange={(value) => updatePreference('language', value)}
                        >
                            <SelectTrigger id="language">
                                <SelectValue placeholder="Select language" />
                            </SelectTrigger>
                            <SelectContent>
                                {languages.map((l) => (
                                    <SelectItem key={l.value} value={l.value}>
                                        {l.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Default Transaction Type */}
                    <div className="space-y-2">
                        <Label htmlFor="default_transaction_type">Default Transaction Type</Label>
                        <Select
                            value={preferences.default_transaction_type}
                            onValueChange={(value) => updatePreference('default_transaction_type', value)}
                        >
                            <SelectTrigger id="default_transaction_type">
                                <SelectValue placeholder="Select default type" />
                            </SelectTrigger>
                            <SelectContent>
                                {transactionTypes.map((t) => (
                                    <SelectItem key={t.value} value={t.value}>
                                        {t.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Notifications */}
                <div className="space-y-4 pt-4 border-t">
                    <h4 className="text-sm font-medium">Notifications</h4>
                    
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="email_notifications" className="text-base">Email Notifications</Label>
                            <p className="text-sm text-muted-foreground">Receive updates and alerts via email</p>
                        </div>
                        <Switch
                            id="email_notifications"
                            checked={preferences.email_notifications}
                            onCheckedChange={(checked) => updatePreference('email_notifications', checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="push_notifications" className="text-base">Push Notifications</Label>
                            <p className="text-sm text-muted-foreground">Receive browser push notifications</p>
                        </div>
                        <Switch
                            id="push_notifications"
                            checked={preferences.push_notifications}
                            onCheckedChange={(checked) => updatePreference('push_notifications', checked)}
                        />
                    </div>
                </div>

                <div className="pt-4">
                    <Button onClick={handleSave} disabled={saving}>
                        {saving ? 'Saving...' : 'Save Preferences'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

// API Keys Component
function ApiKeysSection() {
    const [apiKeys, setApiKeys] = useState<ApiKey[]>([]);
    const [loading, setLoading] = useState(true);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [newKeyName, setNewKeyName] = useState('');
    const [creating, setCreating] = useState(false);
    const [newKey, setNewKey] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        loadApiKeys();
    }, []);

    const loadApiKeys = async () => {
        try {
            const { data } = await getApiKeys();
            setApiKeys(data.apiKeys || []);
        } catch (err: any) {
            setError(err.message || 'Failed to load API keys');
        } finally {
            setLoading(false);
        }
    };

    const handleCreate = async () => {
        if (!newKeyName.trim()) return;
        setCreating(true);
        setError('');

        try {
            const { data } = await createApiKey({ name: newKeyName });
            setNewKey(data.apiKey.key);
            setApiKeys([...apiKeys, data.apiKey]);
            setNewKeyName('');
        } catch (err: any) {
            setError(err.message || 'Failed to create API key');
        } finally {
            setCreating(false);
        }
    };

    const handleCopy = async (key: string) => {
        try {
            await navigator.clipboard.writeText(key);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
    };

    const handleDelete = async (uuid: string) => {
        if (!confirm('Are you sure you want to delete this API key? This action cannot be undone.')) return;

        try {
            await deleteApiKey(uuid);
            setApiKeys(apiKeys.filter(k => k.uuid !== uuid));
        } catch (err: any) {
            setError(err.message || 'Failed to delete API key');
        }
    };

    const closeNewKeyDialog = () => {
        setNewKey(null);
        setDialogOpen(false);
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Never';
        return new Date(dateString).toLocaleString();
    };

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>API Keys</CardTitle>
                    <CardDescription>Manage API keys for external integrations</CardDescription>
                </div>
                <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                    <DialogTrigger asChild>
                        <Button size="sm">
                            <PlusIcon className="mr-2 h-4 w-4" />
                            Create Key
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        {newKey ? (
                            <>
                                <DialogHeader>
                                    <DialogTitle>API Key Created</DialogTitle>
                                    <DialogDescription>
                                        Copy this key now. You won't be able to see it again!
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    <div className="flex items-center gap-2">
                                        <Input value={newKey} readOnly className="font-mono text-sm" />
                                        <Button
                                            size="icon"
                                            variant="outline"
                                            onClick={() => handleCopy(newKey)}
                                        >
                                            {copied ? <CheckIcon className="h-4 w-4" /> : <CopyIcon className="h-4 w-4" />}
                                        </Button>
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button onClick={closeNewKeyDialog}>Done</Button>
                                </DialogFooter>
                            </>
                        ) : (
                            <>
                                <DialogHeader>
                                    <DialogTitle>Create API Key</DialogTitle>
                                    <DialogDescription>
                                        Give your API key a name to help you identify it later.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4 py-4">
                                    {error && (
                                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                                            {error}
                                        </div>
                                    )}
                                    <div className="space-y-2">
                                        <Label htmlFor="keyName">Key Name</Label>
                                        <Input
                                            id="keyName"
                                            placeholder="e.g., Mobile App, Integration"
                                            value={newKeyName}
                                            onChange={(e) => setNewKeyName(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button onClick={handleCreate} disabled={!newKeyName.trim() || creating}>
                                        {creating ? 'Creating...' : 'Create Key'}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="text-center text-muted-foreground py-8">Loading API keys...</div>
                ) : error && apiKeys.length === 0 ? (
                    <div className="text-center text-destructive py-8">{error}</div>
                ) : apiKeys.length === 0 ? (
                    <div className="text-center text-muted-foreground py-8">
                        No API keys created yet. Create one to get started.
                    </div>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead>Last Used</TableHead>
                                <TableHead className="w-[100px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {apiKeys.map((key) => (
                                <TableRow key={key.uuid}>
                                    <TableCell className="font-medium">{key.name}</TableCell>
                                    <TableCell>{formatDate(key.created_at)}</TableCell>
                                    <TableCell>{formatDate(key.last_used_at)}</TableCell>
                                    <TableCell>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleDelete(key.uuid)}
                                            className="text-destructive hover:text-destructive"
                                        >
                                            <TrashIcon className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}

// SMS Parser Rules Component
function SmsParserRulesSection() {
    const [rules, setRules] = useState<SmsParserRule[]>([]);
    const [loading, setLoading] = useState(true);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingRule, setEditingRule] = useState<SmsParserRule | null>(null);
    const [formData, setFormData] = useState({
        name: '',
        bank_name: '',
        pattern: '',
        is_active: true,
    });
    const [saving, setSaving] = useState(false);
    const [testDialogOpen, setTestDialogOpen] = useState(false);
    const [testSms, setTestSms] = useState('');
    const [testResult, setTestResult] = useState<any>(null);
    const [testing, setTesting] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        loadRules();
    }, []);

    const loadRules = async () => {
        try {
            const { data } = await getSmsParserRules();
            setRules(data.rules || []);
        } catch (err: any) {
            setError(err.message || 'Failed to load SMS parser rules');
        } finally {
            setLoading(false);
        }
    };

    const handleSave = async () => {
        setSaving(true);
        setError('');

        try {
            if (editingRule) {
                await updateSmsParserRule(editingRule.uuid, formData);
            } else {
                await createSmsParserRule(formData);
            }
            await loadRules();
            setDialogOpen(false);
            setEditingRule(null);
            setFormData({ name: '', bank_name: '', pattern: '', is_active: true });
        } catch (err: any) {
            setError(err.message || 'Failed to save rule');
        } finally {
            setSaving(false);
        }
    };

    const handleEdit = (rule: SmsParserRule) => {
        setEditingRule(rule);
        setFormData({
            name: rule.name,
            bank_name: rule.bank_name,
            pattern: rule.pattern,
            is_active: rule.is_active,
        });
        setDialogOpen(true);
    };

    const handleCreate = () => {
        setEditingRule(null);
        setFormData({ name: '', bank_name: '', pattern: '', is_active: true });
        setDialogOpen(true);
    };

    const handleDelete = async (uuid: string) => {
        if (!confirm('Are you sure you want to delete this rule?')) return;

        try {
            await deleteSmsParserRule(uuid);
            setRules(rules.filter(r => r.uuid !== uuid));
        } catch (err: any) {
            setError(err.message || 'Failed to delete rule');
        }
    };

    const handleTest = async () => {
        if (!testSms.trim()) return;
        setTesting(true);
        setError('');

        try {
            const { data } = await testSmsParserRule({
                sms: testSms,
                pattern: formData.pattern || undefined,
            });
            setTestResult(data);
        } catch (err: any) {
            setError(err.message || 'Failed to test parser');
        } finally {
            setTesting(false);
        }
    };

    const openTestDialog = () => {
        setTestSms('');
        setTestResult(null);
        setTestDialogOpen(true);
    };

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>SMS Parser Rules</CardTitle>
                    <CardDescription>Configure regex patterns to parse bank SMS messages</CardDescription>
                </div>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={openTestDialog}>
                        <PlayIcon className="mr-2 h-4 w-4" />
                        Test Parser
                    </Button>
                    <Button size="sm" onClick={handleCreate}>
                        <PlusIcon className="mr-2 h-4 w-4" />
                        Add Rule
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                {loading ? (
                    <div className="text-center text-muted-foreground py-8">Loading rules...</div>
                ) : error && rules.length === 0 ? (
                    <div className="text-center text-destructive py-8">{error}</div>
                ) : rules.length === 0 ? (
                    <div className="text-center text-muted-foreground py-8">
                        No parser rules created yet. Add one to start parsing SMS messages.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {rules.map((rule) => (
                            <div
                                key={rule.uuid}
                                className="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                            >
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">{rule.name}</span>
                                        <Badge variant={rule.is_active ? "default" : "secondary"}>
                                            {rule.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground">{rule.bank_name}</p>
                                    <code className="text-xs bg-muted px-2 py-1 rounded">{rule.pattern}</code>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => handleEdit(rule)}>
                                        Edit
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleDelete(rule.uuid)}
                                        className="text-destructive hover:text-destructive"
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            {/* Edit/Create Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editingRule ? 'Edit Rule' : 'Create Rule'}</DialogTitle>
                        <DialogDescription>
                            Configure a regex pattern to parse SMS messages from your bank.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        {error && (
                            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                                {error}
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="ruleName">Rule Name</Label>
                            <Input
                                id="ruleName"
                                placeholder="e.g., Maybank Debit"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="bankName">Bank Name</Label>
                            <Input
                                id="bankName"
                                placeholder="e.g., Maybank"
                                value={formData.bank_name}
                                onChange={(e) => setFormData({ ...formData, bank_name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="pattern">Regex Pattern</Label>
                            <Textarea
                                id="pattern"
                                placeholder="e.g., /RM([\d,.]+).*?(?:debited|paid).*?to\s+(.+?)(?:\s+on|$)/i"
                                value={formData.pattern}
                                onChange={(e) => setFormData({ ...formData, pattern: e.target.value })}
                                rows={3}
                            />
                            <p className="text-xs text-muted-foreground">
                                Use named groups like (?P<amount>...) and (?P<description>...) to capture data.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                id="isActive"
                                checked={formData.is_active}
                                onCheckedChange={(checked) => setFormData({ ...formData, is_active: checked })}
                            />
                            <Label htmlFor="isActive">Active</Label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleSave} disabled={saving || !formData.name || !formData.pattern}>
                            {saving ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Test Dialog */}
            <Dialog open={testDialogOpen} onOpenChange={setTestDialogOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Test SMS Parser</DialogTitle>
                        <DialogDescription>
                            Test your SMS parser with a sample message.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        {error && (
                            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                                {error}
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="testSms">Sample SMS</Label>
                            <Textarea
                                id="testSms"
                                placeholder="Paste your bank SMS here..."
                                value={testSms}
                                onChange={(e) => setTestSms(e.target.value)}
                                rows={4}
                            />
                        </div>
                        {testResult && (
                            <div className="space-y-2">
                                <Label>Result</Label>
                                <div className="bg-muted p-3 rounded text-sm font-mono overflow-auto max-h-40">
                                    <pre>{JSON.stringify(testResult, null, 2)}</pre>
                                </div>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTestDialogOpen(false)}>
                            Close
                        </Button>
                        <Button onClick={handleTest} disabled={!testSms.trim() || testing}>
                            {testing ? 'Testing...' : 'Test'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

export default function Index({ auth }: { auth: { user: User } }) {
    const [activeTab, setActiveTab] = useState('account');
    const [name, setName] = useState(auth.user.name);
    const [email, setEmail] = useState(auth.user.email);
    const [currentPassword, setCurrentPassword] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [loadingProfile, setLoadingProfile] = useState(false);
    const [loadingPassword, setLoadingPassword] = useState(false);
    const [profileMessage, setProfileMessage] = useState('');
    const [passwordMessage, setPasswordMessage] = useState('');
    const [profileError, setProfileError] = useState('');
    const [passwordError, setPasswordError] = useState('');
    const [isProfileOpen, setIsProfileOpen] = useState(true);
    const [isPasswordOpen, setIsPasswordOpen] = useState(false);

    // Auto-dismiss profile messages after 5 seconds
    useEffect(() => {
        if (profileMessage || profileError) {
            const timer = setTimeout(() => {
                setProfileMessage('');
                setProfileError('');
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [profileMessage, profileError]);

    // Auto-dismiss password messages after 5 seconds
    useEffect(() => {
        if (passwordMessage || passwordError) {
            const timer = setTimeout(() => {
                setPasswordMessage('');
                setPasswordError('');
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [passwordMessage, passwordError]);

    const handleSaveProfile = () => {
        setProfileError('');
        setProfileMessage('');

        if (loadingProfile) return;
        setLoadingProfile(true);

        updateUserProfile({ name, email, currentPassword: undefined, password: undefined })
            .then(({ data }) => {
                setProfileMessage('Profile updated successfully');
                setLoadingProfile(false);
            })
            .catch((err) => {
                setProfileError(err.message || 'Failed to update profile');
                setLoadingProfile(false);
            });
    };

    const handleChangePassword = () => {
        setPasswordError('');
        setPasswordMessage('');

        // Validate password match
        if (password !== confirmPassword) {
            setPasswordError('New passwords do not match');
            return;
        }

        // Require current password
        if (!currentPassword) {
            setPasswordError('Current password is required');
            return;
        }

        if (loadingPassword) return;
        setLoadingPassword(true);

        updateUserProfile({ name, email, currentPassword, password })
            .then(({ data }) => {
                setPasswordMessage('Password changed successfully');
                setCurrentPassword('');
                setPassword('');
                setConfirmPassword('');
                setLoadingPassword(false);
            })
            .catch((err) => {
                setPasswordError(err.message || 'Failed to change password');
                setLoadingPassword(false);
            });
    };

    const handleLogout = () => {
        router.post(route('logout'));
    };

    const isProfileValid = name.trim() !== '' && email.trim() !== '';
    const isPasswordValid = currentPassword && password && confirmPassword && password === confirmPassword && password.length >= 8;

    return (
        <>
            <Head title="Settings" />
            <SidebarProvider>
                <Sidebar variant="inset">
                    <SidebarHeader>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton size="lg" asChild>
                                    <Link href={route('dashboard')} className="flex items-center gap-2">
                                        <CaretLeftIcon size={20} />
                                        <ApplicationLogo />
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarHeader>
                    <SidebarContent>
                        {settingsNavItems.map((section) => (
                            <SidebarGroup key={section.section}>
                                <SidebarGroupLabel>{section.section}</SidebarGroupLabel>
                                <SidebarGroupContent>
                                    <SidebarMenu>
                                        {section.items.map((item) => (
                                            <SidebarMenuItem key={item.value}>
                                                <SidebarMenuButton
                                                    onClick={() => setActiveTab(item.value)}
                                                    isActive={activeTab === item.value}
                                                >
                                                    <item.icon />
                                                    <span>{item.title}</span>
                                                </SidebarMenuButton>
                                            </SidebarMenuItem>
                                        ))}
                                    </SidebarMenu>
                                </SidebarGroupContent>
                            </SidebarGroup>
                        ))}

                        {/* Logout Button */}
                        <SidebarGroup>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton
                                            onClick={handleLogout}
                                            className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                        >
                                            <SignOutIcon />
                                            <span>Logout</span>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>
                    </SidebarContent>
                </Sidebar>
                <SidebarInset>
                    <header className="flex h-16 shrink-0 items-center justify-center gap-2 border-b px-4 sticky top-0 bg-background z-10">
                        <div className="flex items-center gap-2 w-full max-w-7xl">
                            <SidebarTrigger className="-ml-1" />
                            <h2 className="text-lg">Settings</h2>
                        </div>
                    </header>
                    <main className="flex flex-1 flex-col gap-4 p-4 items-center">
                        <div className="w-full max-w-7xl">
                            {activeTab === 'account' && (
                                <div className="space-y-4">
                                    {/* Profile Information Section */}
                                    <Collapsible open={isProfileOpen} onOpenChange={setIsProfileOpen}>
                                        <Card>
                                            <CollapsibleTrigger className="w-full">
                                                <CardHeader className="cursor-pointer hover:bg-accent/50 transition-colors">
                                                    <div className="flex items-center justify-between">
                                                        <div className="text-left">
                                                            <CardTitle>Profile Information</CardTitle>
                                                            <CardDescription>
                                                                Update your name and email address
                                                            </CardDescription>
                                                        </div>
                                                        <CaretDownIcon
                                                            size={20}
                                                            className={`transition-transform ${isProfileOpen ? 'rotate-180' : ''}`}
                                                        />
                                                    </div>
                                                </CardHeader>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <CardContent className="space-y-4">
                                                    {profileMessage && (
                                                        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                                                            {profileMessage}
                                                        </div>
                                                    )}

                                                    {profileError && (
                                                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                                                            {profileError}
                                                        </div>
                                                    )}

                                                    <div className="space-y-2">
                                                        <Label htmlFor="name">Name</Label>
                                                        <Input
                                                            id="name"
                                                            type="text"
                                                            value={name}
                                                            onChange={(e) => setName(e.target.value)}
                                                            placeholder="Enter your name"
                                                        />
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="email">Email</Label>
                                                        <Input
                                                            id="email"
                                                            type="email"
                                                            value={email}
                                                            onChange={(e) => setEmail(e.target.value)}
                                                            placeholder="Enter your email"
                                                        />
                                                    </div>

                                                    <div className="pt-2">
                                                        <Button
                                                            onClick={handleSaveProfile}
                                                            disabled={!isProfileValid || loadingProfile}
                                                            className="w-full sm:w-auto"
                                                        >
                                                            {loadingProfile ? 'Saving...' : 'Save Profile'}
                                                        </Button>
                                                    </div>
                                                </CardContent>
                                            </CollapsibleContent>
                                        </Card>
                                    </Collapsible>

                                    {/* Change Password Section */}
                                    <Collapsible open={isPasswordOpen} onOpenChange={setIsPasswordOpen}>
                                        <Card>
                                            <CollapsibleTrigger className="w-full">
                                                <CardHeader className="cursor-pointer hover:bg-accent/50 transition-colors">
                                                    <div className="flex items-center justify-between">
                                                        <div className="text-left">
                                                            <CardTitle>Change Password</CardTitle>
                                                            <CardDescription>
                                                                Update your password
                                                            </CardDescription>
                                                        </div>
                                                        <CaretDownIcon
                                                            size={20}
                                                            className={`transition-transform ${isPasswordOpen ? 'rotate-180' : ''}`}
                                                        />
                                                    </div>
                                                </CardHeader>
                                            </CollapsibleTrigger>
                                            <CollapsibleContent>
                                                <CardContent className="space-y-4">
                                                    {passwordMessage && (
                                                        <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                                                            {passwordMessage}
                                                        </div>
                                                    )}

                                                    {passwordError && (
                                                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                                                            {passwordError}
                                                        </div>
                                                    )}

                                                    <div className="space-y-2">
                                                        <Label htmlFor="currentPassword">Current Password</Label>
                                                        <Input
                                                            id="currentPassword"
                                                            type="password"
                                                            value={currentPassword}
                                                            onChange={(e) => setCurrentPassword(e.target.value)}
                                                            placeholder="Enter current password"
                                                        />
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="password">New Password</Label>
                                                        <Input
                                                            id="password"
                                                            type="password"
                                                            value={password}
                                                            onChange={(e) => setPassword(e.target.value)}
                                                            placeholder="Enter new password (min. 8 characters)"
                                                        />
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="confirmPassword">Confirm New Password</Label>
                                                        <Input
                                                            id="confirmPassword"
                                                            type="password"
                                                            value={confirmPassword}
                                                            onChange={(e) => setConfirmPassword(e.target.value)}
                                                            placeholder="Confirm new password"
                                                        />
                                                    </div>

                                                    <div className="pt-2">
                                                        <Button
                                                            onClick={handleChangePassword}
                                                            disabled={!isPasswordValid || loadingPassword}
                                                            className="w-full sm:w-auto"
                                                        >
                                                            {loadingPassword ? 'Changing...' : 'Change Password'}
                                                        </Button>
                                                    </div>
                                                </CardContent>
                                            </CollapsibleContent>
                                        </Card>
                                    </Collapsible>
                                </div>
                            )}

                            {activeTab === 'preferences' && <PreferencesSection />}

                            {activeTab === 'api-key' && <ApiKeysSection />}

                            {activeTab === 'sms-parser-rules' && <SmsParserRulesSection />}

                            {/* Placeholder pages for other settings */}
                            {activeTab === 'import' && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Import</CardTitle>
                                        <CardDescription>Import your data</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground">Coming soon...</p>
                                    </CardContent>
                                </Card>
                            )}

                            {activeTab === 'export' && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Export</CardTitle>
                                        <CardDescription>Export your data</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground">Coming soon...</p>
                                    </CardContent>
                                </Card>
                            )}

                            {activeTab === 'tags' && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Tags</CardTitle>
                                        <CardDescription>Manage transaction tags</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground">Coming soon...</p>
                                    </CardContent>
                                </Card>
                            )}

                            {activeTab === 'product-updates' && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Product Updates</CardTitle>
                                        <CardDescription>Stay updated with the latest features</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground">Coming soon...</p>
                                    </CardContent>
                                </Card>
                            )}

                            {activeTab === 'feedback' && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Feedback</CardTitle>
                                        <CardDescription>Share your feedback with us</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-muted-foreground">Coming soon...</p>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </main>
                </SidebarInset>
            </SidebarProvider>
        </>
    );
}
