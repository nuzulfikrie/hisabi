<?php

namespace App\Http\Controllers;

use App\Contracts\ReportManager;
use App\Enums\ExportFormat;
use App\Exports\ReportsExport;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Export transactions.
     */
    public function transactions(): BinaryFileResponse
    {
        $format = request()->get('format', ExportFormat::EXCEL->value);
        $filename = export_filename('transactions', $format);

        return Excel::download(
            new TransactionsExport(request()->all()),
            $filename
        );
    }

    /**
     * Export financial report.
     */
    public function report(): BinaryFileResponse
    {
        $sections = app(ReportManager::class)->generate(
            request()->start_date,
            request()->end_date
        );

        $filename = export_filename('report', 'xlsx');

        return Excel::download(
            new ReportsExport($sections, config('hisabi.currency'), request('range', 'Current Period')),
            $filename
        );
    }
}
