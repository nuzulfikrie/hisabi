<?php

namespace App\Jobs;

use App\Enums\ExportFormat;
use App\Exports\TransactionsExport;
use App\Models\User;
use App\Services\Exports\TransactionExportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Domains\Transaction\Models\Transaction;

class ExportTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    public function __construct(
        protected int $userId,
        protected array $filters,
        protected string $format,
        protected string $filePath
    ) {}

    public function handle(): void
    {
        try {
            $format = ExportFormat::tryFrom($this->format) ?? ExportFormat::EXCEL;
            
            // Build query with user filter
            $query = Transaction::query()
                ->where('user_id', $this->userId);

            // Apply date filters with default one month window
            if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
                $query->whereDate('created_at', '>=', $this->filters['start_date'])
                      ->whereDate('created_at', '<=', $this->filters['end_date']);
            } else {
                // Default to current month if no dates provided
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ]);
            }

            if (!empty($this->filters['brand_id'])) {
                $query->where('brand_id', $this->filters['brand_id']);
            }

            // Get the records
            $records = $query->with(['brand', 'brand.category'])->orderByDesc('created_at')->get();

            // Export based on format
            if ($format === ExportFormat::CSV) {
                $this->exportAsCsv($records);
            } else {
                $this->exportAsExcel($records);
            }

            // TODO: Notify user that export is ready
            // For now, we'll store the file and it can be downloaded via a URL
            
        } catch (\Exception $e) {
            Log::error('Export failed', [
                'user_id' => $this->userId,
                'filters' => $this->filters,
                'format' => $this->format,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function exportAsCsv($records): void
    {
        $headers = [
            'ID',
            'Date',
            'Description',
            'Brand',
            'Category',
            'Type',
            'Amount',
            'Created At',
        ];

        // Ensure directory exists
        $directory = dirname(Storage::disk('local')->path($this->filePath));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen(Storage::disk('local')->path($this->filePath), 'w');
        
        // Write UTF-8 BOM
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($handle, $headers);

        // Write data
        foreach ($records as $transaction) {
            fputcsv($handle, [
                $transaction->id,
                $transaction->created_at->format('Y-m-d'),
                $transaction->description ?? $transaction->brand?->name,
                $transaction->brand?->name,
                $transaction->brand?->category?->name ?? $transaction->brand?->category?->type,
                $transaction->type ?? '',
                number_format($transaction->amount, 2),
                $transaction->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($handle);
    }

    protected function exportAsExcel($records): void
    {
        // Build a temporary export class with pre-fetched records
        $export = new class($records) implements \Maatwebsite\Excel\Concerns\FromCollection, 
                                                \Maatwebsite\Excel\Concerns\WithHeadings, 
                                                \Maatwebsite\Excel\Concerns\WithMapping {
            protected $records;
            
            public function __construct($records)
            {
                $this->records = $records;
            }
            
            public function collection()
            {
                return $this->records;
            }
            
            public function headings(): array
            {
                return [
                    'ID',
                    'Date',
                    'Description',
                    'Brand',
                    'Category',
                    'Type',
                    'Amount',
                    'Created At',
                ];
            }
            
            public function map($transaction): array
            {
                return [
                    $transaction->id,
                    $transaction->created_at->format('Y-m-d'),
                    $transaction->description ?? $transaction->brand?->name,
                    $transaction->brand?->name,
                    $transaction->brand?->category?->name ?? $transaction->brand?->category?->type,
                    $transaction->type ?? '',
                    $transaction->amount,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            }
        };
        
        Excel::store($export, $this->filePath, 'local');
    }
}
