<?php

namespace App\Exports;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $this->validateFilters($filters);
    }

    /**
     * Validate and sanitize filters.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    protected function validateFilters(array $filters): array
    {
        $validated = [];

        if (! empty($filters['start_date'])) {
            $validated['start_date'] = $filters['start_date'];
        }

        if (! empty($filters['end_date'])) {
            $validated['end_date'] = $filters['end_date'];
        }

        if (! empty($filters['brand_id']) && is_numeric($filters['brand_id'])) {
            $validated['brand_id'] = (int) $filters['brand_id'];
        }

        return $validated;
    }

    public function collection()
    {
        $query = Transaction::query()
            ->where('user_id', Auth::id());

        if (! empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        if (! empty($this->filters['brand_id'])) {
            $query->where('brand_id', $this->filters['brand_id']);
        }

        return $query->with(['brand', 'brand.category'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Brand',
            'Category',
            'Amount',
            'Created At',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            $transaction->created_at->format('Y-m-d'),
            $transaction->brand?->name,
            $transaction->brand?->category?->name ?? $transaction->brand?->category?->type,
            $transaction->amount,
            $transaction->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
