<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Contracts\Ocr\OcrEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PaddleOCR Service - High accuracy OCR via Docker microservice
 */
class PaddleOcrService implements OcrEngine
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('ocr.paddle.url', 'http://localhost:8000');
        $this->timeout = config('ocr.paddle.timeout', 120);
    }

    /**
     * Extract text from image using PaddleOCR
     */
    public function extract(string $imagePath): string
    {
        if (! file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        try {
            Log::info('PaddleOCR: Processing image', ['path' => $imagePath]);

            $response = Http::timeout($this->timeout)
                ->attach(
                    'image',
                    file_get_contents($imagePath),
                    basename($imagePath)
                )
                ->post("{$this->baseUrl}/ocr");

            if ($response->failed()) {
                Log::error('PaddleOCR: HTTP request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("PaddleOCR request failed: {$response->status()}");
            }

            $data = $response->json();

            if (isset($data['error'])) {
                throw new \RuntimeException("PaddleOCR error: {$data['error']}");
            }

            $text = $data['text'] ?? '';
            $confidence = $data['confidence'] ?? 0.0;
            $wordCount = $data['word_count'] ?? 0;

            Log::info('PaddleOCR: Extraction complete', [
                'confidence' => $confidence,
                'word_count' => $wordCount,
                'text_length' => strlen($text),
            ]);

            return $text;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('PaddleOCR: Connection failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('PaddleOCR service unavailable. Is the Docker container running?');
        } catch (\Exception $e) {
            Log::error('PaddleOCR: Extraction failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Extract with full metadata
     */
    public function extractDetailed(string $imagePath): array
    {
        if (! file_exists($imagePath)) {
            throw new \RuntimeException("Image file not found: {$imagePath}");
        }

        $response = Http::timeout($this->timeout)
            ->attach(
                'image',
                file_get_contents($imagePath),
                basename($imagePath)
            )
            ->post("{$this->baseUrl}/ocr");

        if ($response->failed()) {
            throw new \RuntimeException("PaddleOCR request failed: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Check if PaddleOCR service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful() && $response->json('status') === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get service name
     */
    public function getName(): string
    {
        return 'PaddleOCR';
    }
}
