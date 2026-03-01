import { useState, useRef, useEffect } from 'react';
import { Check, ChevronDown, Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import TagBadge from './TagBadge';

/**
 * TagSelector Component
 * 
 * A multi-select dropdown for selecting tags with color indicators.
 * 
 * @param {Object} props
 * @param {Array} props.availableTags - List of all available tags
 * @param {Array} props.selectedTags - Currently selected tags
 * @param {Function} props.onChange - Callback when selection changes (receives array of tag objects)
 * @param {Function} props.onCreateTag - Callback when user wants to create a new tag (receives name)
 * @param {string} props.placeholder - Placeholder text
 * @param {boolean} props.disabled - Whether the selector is disabled
 */
export default function TagSelector({
    availableTags = [],
    selectedTags = [],
    onChange,
    onCreateTag,
    placeholder = 'Select tags...',
    disabled = false,
}) {
    const [open, setOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const inputRef = useRef(null);

    // Focus input when popover opens
    useEffect(() => {
        if (open && inputRef.current) {
            setTimeout(() => inputRef.current?.focus(), 100);
        }
    }, [open]);

    // Filter available tags based on search query
    const filteredTags = availableTags.filter(tag =>
        tag.name.toLowerCase().includes(searchQuery.toLowerCase())
    );

    // Check if a tag is selected
    const isSelected = (tag) => selectedTags.some(t => t.uuid === tag.uuid);

    // Toggle tag selection
    const toggleTag = (tag) => {
        if (isSelected(tag)) {
            onChange(selectedTags.filter(t => t.uuid !== tag.uuid));
        } else {
            onChange([...selectedTags, tag]);
        }
    };

    // Remove a tag from selection
    const removeTag = (tagUuid, e) => {
        e?.stopPropagation();
        onChange(selectedTags.filter(t => t.uuid !== tagUuid));
    };

    // Handle creating a new tag
    const handleCreateTag = () => {
        if (searchQuery.trim() && onCreateTag) {
            onCreateTag(searchQuery.trim());
            setSearchQuery('');
        }
    };

    // Check if search query matches an existing tag (case-insensitive)
    const tagExists = filteredTags.some(
        tag => tag.name.toLowerCase() === searchQuery.toLowerCase()
    );

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between min-h-[40px] h-auto"
                    disabled={disabled}
                    onClick={() => setOpen(!open)}
                >
                    <div className="flex flex-wrap gap-1 items-center">
                        {selectedTags.length === 0 ? (
                            <span className="text-muted-foreground">{placeholder}</span>
                        ) : (
                            selectedTags.map(tag => (
                                <TagBadge
                                    key={tag.uuid}
                                    tag={tag}
                                    onRemove={(e) => removeTag(tag.uuid, e)}
                                    size="sm"
                                />
                            ))
                        )}
                    </div>
                    <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[300px] p-0" align="start">
                <div className="flex items-center border-b px-3 py-2">
                    <Input
                        ref={inputRef}
                        placeholder="Search tags..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="border-0 focus-visible:ring-0 focus-visible:ring-offset-0"
                    />
                </div>
                <div className="max-h-[200px] overflow-y-auto">
                    {filteredTags.length === 0 && !searchQuery ? (
                        <div className="py-6 text-center text-sm text-muted-foreground">
                            No tags available.
                        </div>
                    ) : (
                        <>
                            {filteredTags.map(tag => (
                                <button
                                    key={tag.uuid}
                                    onClick={() => toggleTag(tag)}
                                    className={cn(
                                        "w-full flex items-center justify-between px-3 py-2 text-sm hover:bg-accent cursor-pointer",
                                        isSelected(tag) && "bg-accent"
                                    )}
                                >
                                    <div className="flex items-center gap-2">
                                        <span
                                            className="w-3 h-3 rounded-full"
                                            style={{ backgroundColor: tag.color }}
                                        />
                                        <span>{tag.name}</span>
                                    </div>
                                    {isSelected(tag) && (
                                        <Check className="h-4 w-4" />
                                    )}
                                </button>
                            ))}
                            {searchQuery && !tagExists && onCreateTag && (
                                <button
                                    onClick={handleCreateTag}
                                    className="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent cursor-pointer text-muted-foreground border-t"
                                >
                                    <Plus className="h-4 w-4" />
                                    <span>Create &quot;{searchQuery}&quot;</span>
                                </button>
                            )}
                        </>
                    )}
                </div>
                {selectedTags.length > 0 && (
                    <div className="border-t p-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full text-muted-foreground hover:text-foreground"
                            onClick={() => onChange([])}
                        >
                            <X className="h-3 w-3 mr-1" />
                            Clear all
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}
