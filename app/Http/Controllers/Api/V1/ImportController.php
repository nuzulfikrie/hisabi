<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ImportCsvRequest;
use App\Http\Requests\Api\V1\ImportExcelRequest;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService
    ) {}

    /**
     * Import transactions from a CSV file.
     */
    public function importCsv(ImportCsvRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $tempPath = 'imports/' . uniqid() . '.csv';

        // Store the uploaded file temporarily
        $file->storeAs('imports', basename($tempPath));

        $result = $this->importService->importFromCsv($tempPath);

        if (!$result['success'] && $result['imported'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'imported' => 0,
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $this->buildSuccessMessage($result),
            'imported' => $result['imported'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Import transactions from an Excel file.
     */
    public function importExcel(ImportExcelRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $tempPath = 'imports/' . uniqid() . '.' . $extension;

        // Store the uploaded file temporarily
        $file->storeAs('imports', basename($tempPath));

        $result = $this->importService->importFromExcel($tempPath);

        if (!$result['success'] && $result['imported'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'imported' => 0,
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $this->buildSuccessMessage($result),
            'imported' => $result['imported'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Download an import template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $format = request('format', 'csv');

        if ($format === 'excel' || $format === 'xlsx') {
            $filePath = $this->importService->generateExcelTemplate();
            $fileName = 'import_template.xlsx';
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $filePath = $this->importService->generateCsvTemplate();
            $fileName = 'import_template.csv';
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

    /**
     * Build a success message based on import results.
     */
    private function buildSuccessMessage(array $result): string
    {
        $imported = $result['imported'];
        $errorCount = count($result['errors']);

        if ($errorCount === 0) {
            return "Successfully imported {$imported} transaction" . ($imported === 1 ? '' : 's') . '.';
        }

        return "Imported {$imported} transaction" . ($imported === 1 ? '' : 's') .
               " with {$errorCount} error" . ($errorCount === 1 ? '' : 's') . '.';
    }
}
