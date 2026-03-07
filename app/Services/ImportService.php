<?php

namespace App\Services;

use App\Domains\Brand\Models\Brand;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportService
{
    /**
     * Import transactions from a CSV file.
     *
     * @param string $filePath
     * @return array
     */
    public function importFromCsv(string $filePath): array
    {
        $fullPath = Storage::path($filePath);
        $handle = fopen($fullPath, 'r');

        if (!$handle) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Failed to open CSV file'],
            ];
        }

        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Empty CSV file or invalid format'],
            ];
        }

        // Normalize headers (lowercase, trim)
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $imported = 0;
        $errors = [];
        $rowNumber = 1; // Start at 1 since headers is row 0

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $data = array_combine($headers, $row);

            if ($data === false) {
                $errors[] = [
                    'row' => $rowNumber,
                    'error' => 'Column count mismatch',
                ];
                continue;
            }

            $result = $this->processRow($data, $rowNumber);

            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = $result['error'];
            }
        }

        fclose($handle);

        // Clean up temp file
        Storage::delete($filePath);

        return [
            'success' => empty($errors) || $imported > 0,
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Import transactions from an Excel file.
     *
     * @param string $filePath
     * @return array
     */
    public function importFromExcel(string $filePath): array
    {
        $fullPath = Storage::path($filePath);

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                Storage::delete($filePath);
                return [
                    'success' => false,
                    'imported' => 0,
                    'errors' => ['Empty Excel file or missing data'],
                ];
            }

            // First row is headers
            $headers = array_shift($rows);
            $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

            $imported = 0;
            $errors = [];
            $rowNumber = 1;

            foreach ($rows as $row) {
                $rowNumber++;

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [];
                foreach ($headers as $index => $header) {
                    $data[$header] = $row[$index] ?? null;
                }

                $result = $this->processRow($data, $rowNumber);

                if ($result['success']) {
                    $imported++;
                } else {
                    $errors[] = $result['error'];
                }
            }

            // Clean up temp file
            Storage::delete($filePath);

            return [
                'success' => empty($errors) || $imported > 0,
                'imported' => $imported,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Storage::delete($filePath);
            Log::error('Excel import error: ' . $e->getMessage());

            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Failed to parse Excel file: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Process a single row of import data.
     *
     * @param array $data
     * @param int $rowNumber
     * @return array
     */
    private function processRow(array $data, int $rowNumber): array
    {
        // Map various header names
        $date = $this->getValueFromKeys($data, ['date', 'created_at', 'datetime', 'transaction_date', 'date_time']);
        $description = $this->getValueFromKeys($data, ['description', 'note', 'notes', 'details', 'transaction', 'desc']);
        $amount = $this->getValueFromKeys($data, ['amount', 'value', 'price', 'cost', 'total']);
        $categoryName = $this->getValueFromKeys($data, ['category', 'category_name', 'cat']);
        $brandName = $this->getValueFromKeys($data, ['brand', 'brand_name', 'merchant', 'vendor', 'store']);

        // Validation
        if (empty($date)) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'error' => 'Date is required',
                ],
            ];
        }

        if (empty($description)) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'error' => 'Description is required',
                ],
            ];
        }

        if (empty($amount) && $amount !== '0' && $amount !== 0) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'error' => 'Amount is required',
                ],
            ];
        }

        // Parse and validate date
        try {
            $parsedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'error' => 'Invalid date format: ' . $date,
                ],
            ];
        }

        // Parse and validate amount
        $cleanAmount = $this->parseAmount($amount);
        if ($cleanAmount === null) {
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'error' => 'Invalid amount: ' . $amount,
                ],
            ];
        }

        // Determine transaction type based on amount sign
        $isIncome = $cleanAmount > 0;
        $absAmount = abs($cleanAmount);

        // Find or create category
        $category = null;
        if (!empty($categoryName)) {
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['type' => $isIncome ? Category::INCOME : Category::EXPENSES]
            );
        }

        // Find or create brand
        $brand = null;
        if (!empty($brandName)) {
            $brand = Brand::firstOrCreate(
                ['name' => $brandName],
                ['category_id' => $category?->id]
            );
        } else {
            // If no brand, create one from description or use a default
            $brand = Brand::firstOrCreate(
                ['name' => substr($description, 0, 255)],
                ['category_id' => $category?->id]
            );
        }

        // Update brand's category if not set and we have a category
        if ($category && !$brand->category_id) {
            $brand->update(['category_id' => $category->id]);
        }

        // Create transaction
        try {
            Transaction::create([
                'amount' => $absAmount,
                'brand_id' => $brand->id,
                'note' => $description,
                'created_at' => $parsedDate,
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Failed to create transaction: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => [
                    'row' => $rowNumber,
                    'error' => 'Failed to create transaction: ' . $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Get value from data using multiple possible keys.
     *
     * @param array $data
     * @param array $keys
     * @return mixed|null
     */
    private function getValueFromKeys(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return null;
    }

    /**
     * Parse amount value, handling various formats.
     *
     * @param mixed $amount
     * @return float|null
     */
    private function parseAmount(mixed $amount): ?float
    {
        if (is_numeric($amount)) {
            return (float) $amount;
        }

        // Remove currency symbols and whitespace
        $cleaned = preg_replace('/[^\d.\-,]/', '', (string) $amount);

        // Handle European format (1.234,56)
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            if (strrpos($cleaned, ',') > strrpos($cleaned, '.')) {
                // European: 1.234,56
                $cleaned = str_replace('.', '', $cleaned);
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // US: 1,234.56
                $cleaned = str_replace(',', '', $cleaned);
            }
        } elseif (strpos($cleaned, ',') !== false) {
            // Could be decimal separator or thousand separator
            $parts = explode(',', $cleaned);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                // Likely decimal: 1234,56
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // Likely thousand separator: 1,234
                $cleaned = str_replace(',', '', $cleaned);
            }
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Generate a CSV template file.
     *
     * @return string
     */
    public function generateCsvTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Category');
        $sheet->setCellValue('E1', 'Brand');

        // Add example row
        $sheet->setCellValue('A2', '2024-01-15');
        $sheet->setCellValue('B2', 'Grocery shopping at Walmart');
        $sheet->setCellValue('C2', '-45.67');
        $sheet->setCellValue('D2', 'Food');
        $sheet->setCellValue('E2', 'Walmart');

        // Add income example
        $sheet->setCellValue('A3', '2024-01-01');
        $sheet->setCellValue('B3', 'Salary');
        $sheet->setCellValue('C3', '5000.00');
        $sheet->setCellValue('D3', 'Income');
        $sheet->setCellValue('E3', 'Employer');

        // Style headers
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempPath = 'templates/import_template_' . uniqid() . '.csv';
        $fullPath = Storage::path($tempPath);

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Csv($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * Generate an Excel template file.
     *
     * @return string
     */
    public function generateExcelTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'Date');
        $sheet->setCellValue('B1', 'Description');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Category');
        $sheet->setCellValue('E1', 'Brand');

        // Add example row
        $sheet->setCellValue('A2', '2024-01-15');
        $sheet->setCellValue('B2', 'Grocery shopping at Walmart');
        $sheet->setCellValue('C2', '-45.67');
        $sheet->setCellValue('D2', 'Food');
        $sheet->setCellValue('E2', 'Walmart');

        // Add income example
        $sheet->setCellValue('A3', '2024-01-01');
        $sheet->setCellValue('B3', 'Salary');
        $sheet->setCellValue('C3', '5000.00');
        $sheet->setCellValue('D3', 'Income');
        $sheet->setCellValue('E3', 'Employer');

        // Style headers
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempPath = 'templates/import_template_' . uniqid() . '.xlsx';
        $fullPath = Storage::path($tempPath);

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }
}
