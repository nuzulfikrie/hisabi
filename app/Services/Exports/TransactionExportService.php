<?php

namespace App\Services\Exports;

class TransactionExportService extends AbstractExportService
{
    /**
     * Get the headers for the export file.
     *
     * @return array<string, string>
     */
    protected function getExportHeaders(): array
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'brand' => 'Brand',
            'category' => 'Category',
            'amount' => 'Amount',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Resolve a field value from the record.
     */
    protected function resolveField(object $record, string $field): string
    {
        return match ($field) {
            'date' => $record->created_at->format('Y-m-d'),
            'brand' => $record->brand?->name ?? '',
            'category' => $record->brand?->category?->name ?? $record->brand?->category?->type ?? '',
            'amount' => number_format($record->amount, 2),
            default => parent::resolveField($record, $field),
        };
    }
}
