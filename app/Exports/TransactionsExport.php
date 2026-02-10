<?php

namespace App\Exports;

use App\Domains\Transaction\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Transaction::query();

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
