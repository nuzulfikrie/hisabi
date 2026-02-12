<?php

namespace App\Actions\Telegram;

use App\Services\Ocr\OcrManager;
use App\Services\Ocr\ReceiptParser;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Process receipt image through OCR and extract transaction data
 */
class ProcessReceiptImage
{
    use AsAction;

    public function __construct(
        private OcrManager $ocrManager,
        private ReceiptParser $receiptParser
    ) {
    }

    /**
     * Handle the action.
     *
     * @param string $imagePath Path to downloaded image
     * @param string $chatId Telegram chat ID
     * @param string $messageId Telegram message ID
     * @return array<string, mixed> Extracted data with text and parsed fields
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

            // Parse the receipt text using ReceiptParser service
            $parsedData = $this->receiptParser->parse($extractedText);

            return [
                'success' => true,
                'text' => $extractedText,
                'engine' => $ocrResult['engine'],
                'parsed_data' => [
                    'merchant' => $parsedData['merchant'],
                    'amount' => $parsedData['amount'],
                    'date' => $parsedData['date'],
                    'items' => $parsedData['items'],
                ],
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
     * Process as a background job
     */
    public function asJob(string $imagePath, string $chatId, string $messageId): void
    {
        $this->handle($imagePath, $chatId, $messageId);
    }
}
