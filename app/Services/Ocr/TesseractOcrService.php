<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Contracts\Ocr\OcrEngine;
use Illuminate\Support\Facades\Log;

/**
 * Tesseract OCR Service - Local fallback option
 */
class TesseractOcrService implements OcrEngine
{
    private string $lang;
    private string $tesseractPath;

    public function __construct()
    {
        $this->lang = config('ocr.tesseract.lang', 'eng+msa+ara');
        $this->tesseractPath = config('ocr.tesseract.path', 'tesseract');
    }

    /**
     * Extract text using Tesseract OCR
     */
    public function extract(string $imagePath): string
    {
        if (! file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        if (! $this->isAvailable()) {
            throw new \RuntimeException('Tesseract is not installed or not in PATH');
        }

        try {
            Log::info('Tesseract: Processing image', ['path' => $imagePath, 'lang' => $this->lang]);

            $cmd = sprintf(
                '%s %s stdout -l %s 2>/dev/null',
                $this->tesseractPath,
                escapeshellarg($imagePath),
                escapeshellarg($this->lang)
            );

            $output = shell_exec($cmd);
            $text = trim($output);

            Log::info('Tesseract: Extraction complete', ['text_length' => strlen($text)]);

            return $text;

        } catch (\Exception $e) {
            Log::error('Tesseract: Extraction failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Check if Tesseract is installed and available
     */
    public function isAvailable(): bool
    {
        $output = shell_exec('which ' . escapeshellarg($this->tesseractPath) . ' 2>/dev/null');
        return ! empty($output);
    }

    /**
     * Get service name
     */
    public function getName(): string
    {
        return 'Tesseract';
    }

    /**
     * Get available languages
     */
    public function getAvailableLanguages(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $output = shell_exec($this->tesseractPath . ' --list-langs 2>&1');
        $lines = explode("\n", trim($output));
        
        // First line is version info, rest are languages
        array_shift($lines);
        
        return array_filter($lines);
    }
}
