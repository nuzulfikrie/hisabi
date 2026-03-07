import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * TagBadge Component
 * 
 * Displays a tag with its color. Can optionally show a remove button.
 * 
 * @param {Object} props
 * @param {Object} props.tag - The tag object with uuid, name, and color
 * @param {Function} props.onRemove - Optional callback when remove button is clicked
 * @param {string} props.size - Size variant: 'sm' | 'md' | 'lg'
 * @param {string} props.className - Additional CSS classes
 */
export default function TagBadge({
    tag,
    onRemove,
    size = 'md',
    className,
}) {
    const sizeClasses = {
        sm: 'text-xs px-2 py-0.5',
        md: 'text-sm px-2.5 py-0.5',
        lg: 'text-base px-3 py-1',
    };

    // Calculate text color based on background brightness
    const getContrastColor = (hexColor) => {
        const r = parseInt(hexColor.slice(1, 3), 16);
        const g = parseInt(hexColor.slice(3, 5), 16);
        const b = parseInt(hexColor.slice(5, 7), 16);
        const brightness = (r * 299 + g * 587 + b * 114) / 1000;
        return brightness > 128 ? '#000000' : '#FFFFFF';
    };

    const textColor = getContrastColor(tag.color);

    return (
        <span
            className={cn(
                "inline-flex items-center gap-1 rounded-full font-medium transition-colors",
                sizeClasses[size],
                onRemove && "pr-1",
                className
            )}
            style={{
                backgroundColor: tag.color,
                color: textColor,
            }}
        >
            <span className="truncate max-w-[120px]">{tag.name}</span>
            {onRemove && (
                <button
                    onClick={onRemove}
                    className="inline-flex items-center justify-center rounded-full hover:bg-black/10 p-0.5 transition-colors"
                    type="button"
                >
                    <X className="h-3 w-3" />
                </button>
            )}
        </span>
    );
}
