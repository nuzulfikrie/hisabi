<?php

declare(strict_types=1);

namespace App\Contracts\Ocr;

interface OcrEngine
{
    /**
     * Extract text from an image file
     *
     * @param string $imagePath Path to the image file
     * @return string Extracted text
     * @throws \RuntimeException If extraction fails
     */
    public function extract(string $imagePath): string;

    /**
     * Check if this engine is available/ready
     */
    public function isAvailable(): bool;

    /**
     * Get engine name
     */
    public function getName(): string;
}
