<?php

namespace App\Services\Exports;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractExportService
{
    protected Builder $query;

    /**
     * Set the query to be exported.
     *
     * @return $this
     */
    public function setQuery(Builder $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Export the query results as a CSV file.
     */
    public function exportCsv(): StreamedResponse
    {
        $filename = $this->exportFilename('csv');
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // Write headers
            fputcsv($handle, array_values($this->getExportHeaders()));

            // Write data
            foreach ($this->query->cursor() as $record) {
                fputcsv($handle, $this->transformRow($record));
            }

            fclose($handle);
        };

        return response()->stream($callback, Response::HTTP_OK, $headers);
    }

    /**
     * Generate a filename for the export file.
     */
    protected function exportFilename(string $ext = 'csv'): string
    {
        return 'export_'.now()->format('Ymd_His').'.'.$ext;
    }

    /**
     * Get the headers for the export file.
     *
     * @return array<string, string>
     */
    abstract protected function getExportHeaders(): array;

    /**
     * Transform a record into a flat array for export.
     *
     * @param object $record
     * @return array<int, mixed>
     */
    protected function transformRow($record): array
    {
        $row = [];

        foreach (array_keys($this->getExportHeaders()) as $field) {
            $row[] = $this->resolveField($record, $field);
        }

        return $row;
    }

    /**
     * Resolve a field value from the record.
     */
    protected function resolveField(object $record, string $field): string
    {
        return (string) ($record->{$field} ?? '');
    }
}
