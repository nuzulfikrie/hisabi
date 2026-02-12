<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OcrScanRequest;
use App\Services\Ocr\OcrManager;
use App\Services\Ocr\ReceiptParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrController extends Controller
{
    public function __construct(
        private readonly OcrManager $ocrManager,
        private readonly ReceiptParser $receiptParser
    ) {}

    /**
     * Scan an image and return extracted text
     */
    public function scan(OcrScanRequest $request): JsonResponse
    {
        try {
            $image = $request->file('image');
            $tempPath = $this->storeTempImage($image);

            // Check if OCR is available
            if (! $this->ocrManager->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OCR service not available. Please try again later.',
                ], 503);
            }

            // Perform OCR
            $ocrResult = $this->ocrManager->extractDetailed($tempPath);

            // Clean up temp file
            $this->cleanupTempFile($tempPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'text' => $ocrResult['text'],
                    'engine' => $ocrResult['engine'],
                    'word_count' => $ocrResult['word_count'],
                    'char_count' => $ocrResult['char_count'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('OCR scan failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Scan an image, extract and parse receipt data
     */
    public function scanAndParse(OcrScanRequest $request): JsonResponse
    {
        try {
            $image = $request->file('image');
            $tempPath = $this->storeTempImage($image);

            // Check if OCR is available
            if (! $this->ocrManager->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OCR service not available. Please try again later.',
                ], 503);
            }

            // Perform OCR
            $ocrResult = $this->ocrManager->extractDetailed($tempPath);

            // Clean up temp file
            $this->cleanupTempFile($tempPath);

            // Parse the receipt text
            $parsedData = $this->receiptParser->parse($ocrResult['text']);

            return response()->json([
                'success' => true,
                'data' => [
                    'parsed' => [
                        'merchant' => $parsedData['merchant'],
                        'amount' => $parsedData['amount'],
                        'date' => $parsedData['date'],
                        'items' => $parsedData['items'],
                    ],
                    'raw' => [
                        'text' => $ocrResult['text'],
                        'engine' => $ocrResult['engine'],
                        'word_count' => $ocrResult['word_count'],
                        'char_count' => $ocrResult['char_count'],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('OCR scan and parse failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get OCR status and available engines
     */
    public function status(): JsonResponse
    {
        $engines = [];

        foreach ($this->ocrManager->getEngines() as $engine) {
            $engines[] = [
                'name' => $engine->getName(),
                'available' => $engine->isAvailable(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $this->ocrManager->isAvailable(),
                'active_engine' => $this->ocrManager->getActiveEngineName(),
                'engines' => $engines,
            ],
        ]);
    }

    /**
     * Store uploaded image to temp location
     */
    private function storeTempImage($image): string
    {
        $tempDir = storage_path('app/temp/ocr');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . '/' . uniqid('ocr_', true) . '.' . $image->extension();
        $image->move($tempDir, basename($tempPath));

        return $tempPath;
    }

    /**
     * Clean up temporary file
     */
    private function cleanupTempFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
