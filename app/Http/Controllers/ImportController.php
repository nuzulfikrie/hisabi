<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService
    ) {}

    /**
     * Display the import page.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/Import');
    }

    /**
     * Download an import template.
     */
    public function template(Request $request): StreamedResponse
    {
        $format = $request->input('format', 'csv');

        if ($format === 'excel' || $format === 'xlsx') {
            $filePath = $this->importService->generateExcelTemplate();
            $fileName = 'hisabi_import_template.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $filePath = $this->importService->generateCsvTemplate();
            $fileName = 'hisabi_import_template.csv';
            $contentType = 'text/csv';
        }

        return response()->streamDownload(function () use ($filePath) {
            readfile($filePath);
            // Clean up temp file after download
            unlink($filePath);
        }, $fileName, [
            'Content-Type' => $contentType,
        ]);
    }
}
