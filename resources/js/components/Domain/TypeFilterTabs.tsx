import { cn } from '@/lib/utils';

interface TypeFilterTabsProps {
    value: string;
    onChange: (value: string) => void;
}

const TABS = [
    { value: 'all', label: 'All' },
    { value: 'personal', label: 'Personal' },
    { value: 'home', label: 'Home' },
];

export default function TypeFilterTabs({ value, onChange }: TypeFilterTabsProps) {
    return (
        <div className="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit">
            {TABS.map((tab) => (
                <button
                    key={tab.value}
                    onClick={() => onChange(tab.value)}
                    className={cn(
                        "px-4 py-2 text-sm font-medium rounded-md transition-colors",
                        value === tab.value
                            ? "bg-gray-800 text-white shadow-sm"
                            : "text-gray-600 hover:text-gray-900 hover:bg-gray-200"
                    )}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
