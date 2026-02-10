<?php

declare(strict_types=1);

namespace App\Enums;

enum ExportFormat: string
{
    case EXCEL = 'xlsx';
    case CSV = 'csv';
    case PDF = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::EXCEL => __('Excel'),
            self::CSV => __('CSV'),
            self::PDF => __('PDF'),
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::CSV => 'text/csv',
            self::PDF => 'application/pdf',
        };
    }
}
