import { useEffect, useState } from "react";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { createTransaction, createTag, getAllTags } from "@/Api";
import Combobox from "@/components/Global/Combobox";
import TagSelector from "./TagSelector";
import { Button } from "@/components/ui/button";
import { getAppCurrency } from '@/Utils';
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

export default function Create({ brands, showCreate, onClose, onCreate }) {
    const [amount, setAmount] = useState(0);
    const [brand, setBrand] = useState(null);
    const [createdAt, setCreatedAt] = useState('');
    const [note, setNote] = useState('');
    const [type, setType] = useState('personal');
    const [description, setDescription] = useState('');
    const [tags, setTags] = useState([]);
    const [availableTags, setAvailableTags] = useState([]);
    const [isReady, setIsReady] = useState(false);
    const [loading, setLoading] = useState(false);

    // Load available tags on mount
    useEffect(() => {
        getAllTags()
            .then(({ data }) => {
                setAvailableTags(data.allTags || []);
            })
            .catch(console.error);
    }, []);

    useEffect(() => {
        setIsReady(amount != 0 && brand != null && createdAt != '' ? true : false);
    }, [amount, brand, createdAt]);

    // Auto-populate description from brand name when brand changes
    useEffect(() => {
        if (brand && !description) {
            setDescription(brand.name);
        }
    }, [brand]);

    const handleCreateTag = async (name) => {
        // Generate a random color for the new tag
        const colors = [
            '#EF4444', '#F97316', '#F59E0B', '#84CC16', '#10B981',
            '#06B6D4', '#3B82F6', '#8B5CF6', '#D946EF', '#F43F5E'
        ];
        const color = colors[Math.floor(Math.random() * colors.length)];

        try {
            const { data } = await createTag({ name, color });
            const newTag = data.createTag;
            setAvailableTags(prev => [...prev, newTag]);
            setTags(prev => [...prev, newTag]);
        } catch (error) {
            console.error('Failed to create tag:', error);
        }
    };

    const handleCreate = () => {
        if (loading || !isReady || !brand) return;

        setLoading(true);

        createTransaction({
            amount,
            brandId: brand.id,
            createdAt,
            note,
            tags: tags.map(t => t.uuid),
            type,
            description
        })
            .then(({ data }) => {
                onCreate(data.transaction);
                // Reset form
                setBrand(null);
                setAmount(0);
                setCreatedAt('');
                setNote('');
                setType('personal');
                setDescription('');
                setTags([]);
                setLoading(false);
                onClose();
            })
            .catch(console.error);
    };

    return (
        <Dialog open={showCreate} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogTitle className="sr-only">Create Transaction</DialogTitle>
                <div className="space-y-4">
                    <div>
                        <Label htmlFor="amount">
                            {`Amount (${getAppCurrency()})`}
                        </Label>
                        <Input
                            type="number"
                            name="amount"
                            value={amount}
                            className="mt-1"
                            onChange={(e) => setAmount(e.target.value > 0 ? e.target.value : 0)}
                        />
                    </div>

                    <div>
                        <Label htmlFor="date">
                            Date
                        </Label>
                        <Input
                            type="date"
                            name="date"
                            value={createdAt}
                            className="mt-1"
                            onChange={(e) => setCreatedAt(e.target.value)}
                        />
                    </div>

                    <div>
                        <Combobox
                            label="Brand"
                            items={brands}
                            initialSelectedItem={brand}
                            onChange={(item) => setBrand(item)}
                            displayInputValue={(item) => item ? `${item.name} (${item.category?.name ?? 'N/A'})` : ''}
                            displayOptionValue={(item) => item ? `${item.name} (${item.category?.name ?? 'N/A'})` : ''}
                        />
                    </div>

                    <div>
                        <Label htmlFor="description">
                            Description
                        </Label>
                        <Input
                            type="text"
                            name="description"
                            value={description}
                            className="mt-1"
                            placeholder="e.g., Internet, Groceries midweek"
                            onChange={(e) => setDescription(e.target.value)}
                        />
                    </div>

                    <div>
                        <Label htmlFor="type">
                            Type
                        </Label>
                        <Select
                            value={type}
                            onValueChange={setType}
                        >
                            <SelectTrigger className="mt-1 w-full">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="personal">Personal</SelectItem>
                                <SelectItem value="home">Home</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label htmlFor="tags">
                            Tags (optional)
                        </Label>
                        <div className="mt-1">
                            <TagSelector
                                availableTags={availableTags}
                                selectedTags={tags}
                                onChange={setTags}
                                onCreateTag={handleCreateTag}
                                placeholder="Select or create tags..."
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="note">
                            Note (optional)
                        </Label>
                        <Input
                            type="text"
                            name="note"
                            value={note}
                            className="mt-1"
                            onChange={(e) => setNote(e.target.value)}
                        />
                    </div>

                    <div className="flex items-center justify-end pt-2">
                        <Button
                            disabled={!isReady || loading}
                            onClick={handleCreate}
                        >
                            Create
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
