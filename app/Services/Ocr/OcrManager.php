<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Contracts\Ocr\OcrEngine;
use Illuminate\Support\Facades\Log;

/**
 * OCR Manager - Strategy pattern for multiple OCR engines
 * 
 * Priority:
 * 1. PaddleOCR (best accuracy)
 * 2. Tesseract (fallback)
 */
class OcrManager
{
    private array $engines = [];
    private ?OcrEngine $primaryEngine = null;
    private ?OcrEngine $fallbackEngine = null;

    public function __construct()
    {
        $this->registerEngines();
    }

    /**
     * Register available OCR engines
     */
    private function registerEngines(): void
    {
        // Primary: PaddleOCR
        $paddle = new PaddleOcrService();
        if ($paddle->isAvailable()) {
            $this->engines[] = $paddle;
            $this->primaryEngine = $paddle;
            Log::info('OCR Manager: PaddleOCR registered as primary');
        }

        // Fallback: Tesseract
        $tesseract = new TesseractOcrService();
        if ($tesseract->isAvailable()) {
            $this->engines[] = $tesseract;
            if (! $this->primaryEngine) {
                $this->primaryEngine = $tesseract;
            } else {
                $this->fallbackEngine = $tesseract;
            }
            Log::info('OCR Manager: Tesseract registered');
        }

        if (empty($this->engines)) {
            Log::warning('OCR Manager: No OCR engines available');
        }
    }

    /**
     * Extract text from image using best available engine
     * Falls back to secondary engine if primary fails or returns low quality
     */
    public function extract(string $imagePath): string
    {
        if (! $this->primaryEngine) {
            throw new \RuntimeException('No OCR engine available. Please install Tesseract or start PaddleOCR Docker container.');
        }

        // Try primary engine
        try {
            $text = $this->primaryEngine->extract($imagePath);
            
            // If result is too short and we have a fallback, try it
            if (strlen($text) < 20 && $this->fallbackEngine) {
                Log::info('OCR Manager: Primary result low quality, trying fallback');
                $fallbackText = $this->fallbackEngine->extract($imagePath);
                
                // Use fallback if it got better results
                if (strlen($fallbackText) > strlen($text)) {
                    return $fallbackText;
                }
            }
            
            return $text;
            
        } catch (\Exception $e) {
            Log::warning('OCR Manager: Primary engine failed', ['error' => $e->getMessage()]);
            
            // Try fallback if available
            if ($this->fallbackEngine) {
                Log::info('OCR Manager: Trying fallback engine');
                return $this->fallbackEngine->extract($imagePath);
            }
            
            throw $e;
        }
    }

    /**
     * Extract with full details from best engine
     */
    public function extractDetailed(string $imagePath): array
    {
        $text = $this->extract($imagePath);
        
        return [
            'text' => $text,
            'engine' => $this->getActiveEngineName(),
            'word_count' => str_word_count($text),
            'char_count' => strlen($text),
        ];
    }

    /**
     * Get currently active engine name
     */
    public function getActiveEngineName(): string
    {
        return $this->primaryEngine?->getName() ?? 'None';
    }

    /**
     * Get all registered engines
     */
    public function getEngines(): array
    {
        return $this->engines;
    }

    /**
     * Check if any engine is available
     */
    public function isAvailable(): bool
    {
        return $this->primaryEngine !== null;
    }

    /**
     * Force specific engine
     */
    public function using(string $engineName): OcrEngine
    {
        foreach ($this->engines as $engine) {
            if ($engine->getName() === $engineName) {
                return $engine;
            }
        }
        
        throw new \InvalidArgumentException("OCR engine '{$engineName}' not available");
    }
}
