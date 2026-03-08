# Excel/CSV Export

Hisabi supports exporting transactions and reports to Excel and CSV formats using Maatwebsite Excel.

## Features

- Export transactions with filters
- Export financial reports
- Multiple formats (XLSX, CSV)
- Large dataset streaming support

## Usage

### Web Interface

Navigate to reports and click the "Export" button to download:
- Transaction lists
- Financial reports
- Category summaries

### API/Programmatic

```php
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;

// Export all transactions
return Excel::download(new TransactionsExport(), 'transactions.xlsx');

// Export with filters
$filters = [
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'type' => 'expense',
];
return Excel::download(new TransactionsExport($filters), 'transactions.xlsx');

// Export to CSV
return (new TransactionsExport($filters))
    ->download('transactions.csv', \Maatwebsite\Excel\Excel::CSV);
```

## Export Classes

### TransactionsExport

```php
use App\Exports\TransactionsExport;

$export = new TransactionsExport([
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
    'brand_id' => 5,
]);

// Columns exported:
// - ID
// - Date
// - Brand
// - Category
// - Amount
// - Created At
```

### ReportsExport

```php
use App\Exports\ReportsExport;

$sections = app(\App\Contracts\ReportManager::class)
    ->generate('2024-01-01', '2024-12-31');

$export = new ReportsExport($sections, 'MYR', '2024');
```

## Streaming Exports (Large Datasets)

For very large exports, use the `AbstractExportService`:

```php
use App\Services\Exports\AbstractExportService;

class LargeTransactionExport extends AbstractExportService
{
    protected function getExportHeaders(): array
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'amount' => 'Amount',
            'brand' => 'Brand',
        ];
    }
    
    protected function resolveField(object $record, string $field): string
    {
        return match ($field) {
            'brand' => $record->brand?->name ?? '',
            default => (string) $record->{$field},
        };
    }
}

// Usage
$service = new LargeTransactionExport();
$service->setQuery(Transaction::query());
return $service->exportCsv();
```

## Routes

| Route | Description |
|-------|-------------|
| `GET /exports/transactions` | Export transactions |
| `GET /exports/report` | Export financial report |

### Query Parameters

**Transactions Export:**
- `format` - `xlsx` (default) or `csv`
- `start_date` - Filter from date
- `end_date` - Filter to date
- `type` - Filter by type (income/expense)
- `brand_id` - Filter by brand

**Report Export:**
- `start_date` - Report start date
- `end_date` - Report end date
- `range` - Label for date range

## Formats

### Excel (XLSX)

```php
Excel::download($export, 'file.xlsx');
```

Best for:
- Complex formatting
- Multiple sheets
- Formulas
- Large datasets

### CSV

```php
Excel::download($export, 'file.csv', \Maatwebsite\Excel\Excel::CSV);
```

Best for:
- Universal compatibility
- Importing to other systems
- Minimal file size

## Custom Export Class

```php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MyExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return MyModel::all();
    }
    
    public function headings(): array
    {
        return ['ID', 'Name', 'Amount'];
    }
    
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            number_format($row->amount, 2),
        ];
    }
}
```

## Helper Functions

```php
// Generate filename with timestamp
export_filename('transactions', 'xlsx');
// Returns: transactions_20240210_143022.xlsx
```

## Performance Tips

1. **Use cursor for large datasets**:
   ```php
   Model::cursor() // Instead of all()
   ```

2. **Queue exports**:
   ```php
   Excel::queue(new LargeExport(), 'file.xlsx');
   ```

3. **Chunk queries**:
   ```php
   Model::chunk(1000, function ($rows) {
       // Process chunk
   });
   ```

## Configuration

`config/excel.php` (published from Maatwebsite):

```php
return [
    'exports' => [
        'chunk_size' => 1000,
        'temp_path' => storage_path('framework/cache/laravel-excel'),
    ],
];
```
