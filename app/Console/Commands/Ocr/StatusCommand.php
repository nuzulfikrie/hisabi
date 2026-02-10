<?php

namespace App\Console\Commands\Ocr;

use App\Services\Ocr\OcrManager;
use App\Services\Ocr\TesseractOcrService;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'ocr:status';

    protected $description = 'Check OCR service status';

    public function handle(): int
    {
        $this->info('🔍 Checking OCR Services...');
        $this->newLine();

        $manager = new OcrManager();

        // Check PaddleOCR
        $this->info('PaddleOCR (Docker):');
        $paddleUrl = config('ocr.paddle.url');
        $this->line("  URL: {$paddleUrl}");
        
        if ($manager->isAvailable()) {
            $this->info('  ✅ Available');
        } else {
            $this->warn('  ❌ Not Available');
            $this->line('  Run: docker-compose -f docker-compose.paddleocr.yml up -d');
        }
        $this->newLine();

        // Check Tesseract
        $this->info('Tesseract (Local):');
        $tesseract = new TesseractOcrService();
        
        if ($tesseract->isAvailable()) {
            $this->info('  ✅ Installed');
            $this->line('  Path: ' . config('ocr.tesseract.path'));
            $this->line('  Languages: ' . config('ocr.tesseract.lang'));
            
            // Show available languages
            $langs = $tesseract->getAvailableLanguages();
            if (! empty($langs)) {
                $this->line('  Installed packs: ' . implode(', ', array_slice($langs, 0, 10)));
            }
        } else {
            $this->warn('  ❌ Not Installed');
            $this->line('  Install: sudo apt install tesseract-ocr tesseract-ocr-eng tesseract-ocr-msa');
        }
        $this->newLine();

        // Summary
        $this->info('Status:');
        if ($manager->isAvailable()) {
            $this->info('  ✅ OCR is ready');
            $this->line('  Active Engine: ' . $manager->getActiveEngineName());
        } else {
            $this->error('  ❌ No OCR engines available');
            $this->line('  Receipt scanning will not work');
        }

        return 0;
    }
}
