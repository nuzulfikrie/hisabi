<?php

namespace App\Actions\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Download file from Telegram servers
 */
class DownloadTelegramFile
{
    use AsAction;

    private string $tempDirectory;

    public function __construct()
    {
        $this->tempDirectory = config('ocr.image.temp_directory', storage_path('app/temp/ocr'));
        
        // Ensure directory exists
        if (! is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
    }

    /**
     * Download file from Telegram
     *
     * @param string $fileId Telegram file_id
     * @return string Path to downloaded file
     * @throws \RuntimeException If download fails
     */
    public function handle(string $fileId): string
    {
        try {
            Log::info('Downloading Telegram file', ['file_id' => $fileId]);

            // Get file path from Telegram
            $fileInfo = Telegram::getFile(['file_id' => $fileId]);
            $filePath = $fileInfo->get('file_path');

            if (! $filePath) {
                throw new \RuntimeException('Could not get file path from Telegram');
            }

            // Construct download URL
            $botToken = config('telegram.bot_token');
            $downloadUrl = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

            // Download file
            $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
            $localFileName = 'tg_' . uniqid() . '.' . $extension;
            $localPath = $this->tempDirectory . '/' . $localFileName;

            $response = Http::timeout(60)->get($downloadUrl);

            if ($response->failed()) {
                throw new \RuntimeException("Failed to download file: {$response->status()}");
            }

            // Save file
            file_put_contents($localPath, $response->body());

            Log::info('File downloaded successfully', [
                'file_id' => $fileId,
                'local_path' => $localPath,
                'size' => strlen($response->body()),
            ]);

            return $localPath;

        } catch (\Exception $e) {
            Log::error('Failed to download Telegram file', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cleanup temporary file
     */
    public function cleanup(string $filePath): void
    {
        if (file_exists($filePath) && str_starts_with($filePath, $this->tempDirectory)) {
            unlink($filePath);
            Log::debug('Cleaned up temp file', ['path' => $filePath]);
        }
    }
}
