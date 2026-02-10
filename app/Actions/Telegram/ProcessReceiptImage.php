<?php

namespace App\Actions\Telegram;

use App\Services\Ocr\OcrManager;
use App\Models\TelegramTransaction;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Process receipt image through OCR and extract transaction data
 */
class ProcessReceiptImage
{
    use AsAction;

    public function __construct(
        private OcrManager $ocrManager
    ) {
    }

    /**
     * Handle the action.
     *
     * @param string $imagePath Path to downloaded image
     * @param string $chatId Telegram chat ID
     * @param string $messageId Telegram message ID
     * @return array Extracted data with text and parsed fields
     */
    public function handle(string $imagePath, string $chatId, string $messageId): array
    {
        try {
            Log::info('Processing receipt image', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'image_path' => $imagePath,
            ]);

            // Check if OCR is available
            if (! $this->ocrManager->isAvailable()) {
                throw new \RuntimeException('OCR service not available. Please try again later.');
            }

            // Perform OCR
            $ocrResult = $this->ocrManager->extractDetailed($imagePath);
            $extractedText = $ocrResult['text'];

            if (empty($extractedText)) {
                throw new \RuntimeException('No text could be extracted from the image.');
            }

            Log::info('OCR extraction complete', [
                'engine' => $ocrResult['engine'],
                'word_count' => $ocrResult['word_count'],
                'char_count' => $ocrResult['char_count'],
            ]);

            // Try to parse transaction data from extracted text
            $parsedData = $this->parseReceiptText($extractedText);

            return [
                'success' => true,
                'text' => $extractedText,
                'engine' => $ocrResult['engine'],
                'parsed_data' => $parsedData,
            ];

        } catch (\Exception $e) {
            Log::error('Receipt processing failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse receipt text to extract transaction fields
     */
    private function parseReceiptText(string $text): array
    {
        $lines = explode("\n", $text);
        $data = [
            'merchant' => null,
            'amount' => null,
            'date' => null,
            'items' => [],
        ];

        // Try to find amount (look for currency patterns)
        $amountPatterns = [
            '/RM\s*([0-9,]+\.\d{2})/i',           // RM 50.00
            '/TOTAL[\s:]*RM?\s*([0-9,]+\.\d{2})/i', // TOTAL: 50.00
            '/([0-9,]+\.\d{2})\s*RM/i',           // 50.00 RM
            '/\b([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2})\b/', // Generic amount
        ];

        foreach ($amountPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = str_replace(',', '', $matches[1]);
                $data['amount'] = (float) $amount;
                break;
            }
        }

        // Try to find merchant name (usually in first few lines)
        foreach (array_slice($lines, 0, 5) as $line) {
            $line = trim($line);
            if (strlen($line) > 3 && strlen($line) < 50 && ! is_numeric($line)) {
                $data['merchant'] = $line;
                break;
            }
        }

        // Try to find date
        $datePatterns = [
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/',  // DD/MM/YYYY
            '/(\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})/',  // YYYY/MM/DD
            '/(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{4})/i', // 1 Jan 2024
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['date'] = $matches[1];
                break;
            }
        }

        return $data;
    }

    /**
     * Process as a background job
     */
    public function asJob(string $imagePath, string $chatId, string $messageId): void
    {
        $this->handle($imagePath, $chatId, $messageId);
    }
}
