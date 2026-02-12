<?php

namespace App\Http\Controllers;

use App\Contracts\ReportManager;
use App\Domains\Transaction\Models\Transaction;
use App\Enums\ExportFormat;
use App\Exports\ReportsExport;
use App\Exports\TransactionsExport;
use App\Http\Requests\ExportReportRequest;
use App\Http\Requests\ExportTransactionsRequest;
use App\Services\Exports\TransactionExportService;
use Illuminate\Support\Facades\Auth;
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
     */
    public function transactions(ExportTransactionsRequest $request): BinaryFileResponse|StreamedResponse
    {
        $format = ExportFormat::tryFrom($request->validated('format', ExportFormat::EXCEL->value));
        $filters = $request->only(['start_date', 'end_date', 'brand_id']);

        if ($format === ExportFormat::CSV) {
            return $this->exportTransactionsAsCsv($filters);
        }

        // Default to Excel
        $filename = export_filename('transactions', 'xlsx');

        return Excel::download(
            new TransactionsExport($filters),
            $filename
        );
    }

    /**
     * Export transactions as CSV using streaming.
     *
     * @param array<string, mixed> $filters
     */
    protected function exportTransactionsAsCsv(array $filters): StreamedResponse
    {
        $query = Transaction::query()
            ->where('user_id', Auth::id());

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
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
