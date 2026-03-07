import React from 'react';
import { 
    PlusIcon, 
    SaveIcon, 
    DocumentDownloadIcon,
    LightningBoltIcon
} from '@heroicons/react/solid';

import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface QuickActionsWidgetProps {
    onAddTransaction?: () => void;
    onCreateBudget?: () => void;
    onExportReport?: () => void;
}

interface ActionButton {
    id: string;
    label: string;
    icon: React.ReactNode;
    variant: 'default' | 'secondary' | 'outline' | 'ghost';
    href?: string;
    onClick?: () => void;
    color?: string;
}

export default function QuickActionsWidget({ 
    onAddTransaction, 
    onCreateBudget, 
    onExportReport 
}: QuickActionsWidgetProps) {
    const actions: ActionButton[] = [
        {
            id: 'add-transaction',
            label: 'Add Transaction',
            icon: <PlusIcon className="h-5 w-5" />,
            variant: 'default',
            onClick: onAddTransaction,
            color: 'bg-blue-600 hover:bg-blue-700',
        },
        {
            id: 'create-budget',
            label: 'Create Budget',
            icon: <SaveIcon className="h-5 w-5" />,
            variant: 'secondary',
            href: '/budgets/create',
            color: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',
        },
        {
            id: 'export-report',
            label: 'Export Report',
            icon: <DocumentDownloadIcon className="h-5 w-5" />,
            variant: 'outline',
            onClick: onExportReport,
        },
    ];

    const handleClick = (action: ActionButton) => {
        if (action.onClick) {
            action.onClick();
        }
    };

    return (
        <Card className="relative p-6">
            <div className="flex items-center gap-2 mb-4">
                <LightningBoltIcon className="h-5 w-5 text-gray-500" />
                <h3 className="text-base text-gray-600">Quick Actions</h3>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {actions.map((action) => {
                    const buttonContent = (
                        <Button
                            variant={action.variant}
                            className={`w-full h-auto py-3 px-4 flex flex-col items-center gap-2 ${action.color || ''}`}
                            onClick={() => handleClick(action)}
                        >
                            {action.icon}
                            <span className="text-sm font-medium">{action.label}</span>
                        </Button>
                    );

                    if (action.href) {
                        return (
                            <Link key={action.id} href={action.href} className="contents">
                                {buttonContent}
                            </Link>
                        );
                    }

                    return <div key={action.id}>{buttonContent}</div>;
                })}
            </div>

            {/* Mobile-friendly stacked view hint */}
            <div className="mt-4 pt-4 border-t border-gray-100">
                <div className="flex items-center justify-between text-xs text-gray-500">
                    <span>Shortcuts</span>
                    <div className="flex gap-3">
                        <span className="hidden sm:inline">Ctrl+N: New Transaction</span>
                        <span className="hidden sm:inline">Ctrl+B: Budgets</span>
                    </div>
                </div>
            </div>
        </Card>
    );
}
