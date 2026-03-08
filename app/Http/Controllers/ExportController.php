<?php

namespace App\Http\Controllers;

use App\Contracts\ReportManager;
use App\Domains\Transaction\Models\Transaction;
use App\Enums\ExportFormat;
use App\Exports\ReportsExport;
use App\Exports\TransactionsExport;
use App\Http\Requests\ExportReportRequest;
use App\Http\Requests\ExportTransactionsRequest;
use App\Jobs\ExportTransactionsJob;
use App\Services\Exports\TransactionExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Display the exports dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Exports/Index');
    }

    /**
     * Export transactions in the requested format.
     * Offloads to a queue job for async processing.
     */
    public function transactions(ExportTransactionsRequest $request): JsonResponse
    {
        $format = ExportFormat::tryFrom($request->validated('format', ExportFormat::EXCEL->value));
        $filters = $request->only(['start_date', 'end_date', 'brand_id']);

        // Apply default one month window if no dates provided
        if (empty($filters['start_date']) || empty($filters['end_date'])) {
            $filters['start_date'] = Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // Generate unique filename
        $extension = $format === ExportFormat::CSV ? 'csv' : 'xlsx';
        $filename = 'transactions_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $extension;
        $filePath = 'exports/' . Auth::id() . '/' . $filename;

        // Dispatch job to queue
        ExportTransactionsJob::dispatch(
            Auth::id(),
            $filters,
            $format->value,
            $filePath
        );

        return response()->json([
            'success' => true,
            'message' => 'Export is being processed. You will be notified when it is ready.',
            'export_id' => basename($filename, '.' . $extension),
        ]);
    }

    /**
     * Download a completed export file.
     */
    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $filePath = 'exports/' . Auth::id() . '/' . $filename;

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Export file not found or not ready yet.',
            ], 404);
        }

        return response()->download(
            Storage::disk('local')->path($filePath),
            $filename,
            [
                'Content-Type' => str_ends_with($filename, '.csv') 
                    ? 'text/csv' 
                    : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    /**
     * Check status of an export.
     */
    public function status(string $filename): JsonResponse
    {
        $filePath = 'exports/' . Auth::id() . '/' . $filename;
        $exists = Storage::disk('local')->exists($filePath);

        return response()->json([
            'ready' => $exists,
            'download_url' => $exists 
                ? route('exports.download', ['filename' => $filename]) 
                : null,
        ]);
    }

    /**
     * Legacy: Export transactions as CSV using streaming (for small datasets).
     *
     * @param array<string, mixed> $filters
     */
    protected function exportTransactionsAsCsv(array $filters): StreamedResponse
    {
        $query = Transaction::query()
            ->where('user_id', Auth::id());

        // Apply default one month window if no dates provided
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date'])
                  ->whereDate('created_at', '<=', $filters['end_date']);
        } else {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        $service = new TransactionExportService();
        $service->setQuery($query);

        return $service->exportCsv();
    }

    /**
     * Export financial report in the requested format.
     */
    public function report(ExportReportRequest $request): BinaryFileResponse|StreamedResponse
    {
        $format = ExportFormat::tryFrom($request->validated('format', ExportFormat::EXCEL->value));
        $startDate = $request->validated('start_date');
        $endDate = $request->validated('end_date');

        // Apply default to current month if no dates provided
        if (empty($startDate) || empty($endDate)) {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // Get report sections (these already filter by authenticated user)
        $sections = app(ReportManager::class)->generate($startDate, $endDate);

        $range = $startDate && $endDate
            ? $startDate.' - '.$endDate
            : now()->format('F Y');

        if ($format === ExportFormat::CSV) {
            return $this->exportReportAsCsv($sections, $range);
        }

        // Default to Excel
        $filename = export_filename('report', 'xlsx');

        return Excel::download(
            new ReportsExport($sections, config('hisabi.currency', 'MYR'), $range),
            $filename
        );
    }

    /**
     * Export report as CSV using streaming.
     *
     * @param array<string, mixed> $sections
     */
    protected function exportReportAsCsv(array $sections, string $range): StreamedResponse
    {
        $filename = export_filename('report', 'csv');
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $currency = config('hisabi.currency', 'MYR');

        $callback = function () use ($sections, $range, $currency) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // Write header
            fputcsv($handle, ['Financial Report']);
            fputcsv($handle, ['Period:', $range]);
            fputcsv($handle, ['Currency:', $currency]);
            fputcsv($handle, []);

            // Write sections
            foreach ($sections as $sectionName => $sectionData) {
                fputcsv($handle, [ucfirst($sectionName)]);

                if (is_array($sectionData) && ! empty($sectionData)) {
                    if (isset($sectionData[0]) && is_array($sectionData[0])) {
                        // Array of records - write headers
                        fputcsv($handle, array_keys($sectionData[0]));

                        // Write data
                        foreach ($sectionData as $row) {
                            fputcsv($handle, array_values($row));
                        }
                    } else {
                        // Key-value pairs
                        foreach ($sectionData as $key => $value) {
                            if (is_array($value)) {
                                fputcsv($handle, [ucwords(str_replace('_', ' ', $key))]);
                                foreach ($value as $subKey => $subValue) {
                                    fputcsv($handle, [
                                        '  ' . ucwords(str_replace('_', ' ', $subKey)),
                                        is_numeric($subValue) ? number_format($subValue, 2) : $subValue,
                                    ]);
                                }
                            } else {
                                fputcsv($handle, [
                                    ucwords(str_replace('_', ' ', $key)),
                                    is_numeric($value) ? number_format($value, 2) : $value,
                                ]);
                            }
                        }
                    }
                }

                fputcsv($handle, []);
            }

            // Footer
            fputcsv($handle, ['Generated on ' . now()->format('F j, Y \a\t g:i A')]);
            fputcsv($handle, ['Hisabi - Personal Finance Tracker']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
