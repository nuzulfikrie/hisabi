<?php

namespace App\Console\Commands\Ocr;

use App\Services\Ocr\OcrManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestCommand extends Command
{
    protected $signature = 'ocr:test 
                            {image? : Path to test image}
                            {--engine= : Force specific engine (paddle, tesseract)}';

    protected $description = 'Test OCR on an image';

    public function handle(): int
    {
        $imagePath = $this->argument('image');

        if (! $imagePath) {
            // Create a test by downloading a sample receipt image
            $this->warn('No image provided. Testing with PaddleOCR health check...');
            
            $url = config('ocr.paddle.url');
            try {
                $response = Http::timeout(5)->get("{$url}/health");
                if ($response->successful()) {
                    $this->info('✅ PaddleOCR is responding');
                    $this->line('Response: ' . json_encode($response->json()));
                } else {
                    $this->error('❌ PaddleOCR health check failed');
                }
            } catch (\Exception $e) {
                $this->error('❌ Cannot connect to PaddleOCR: ' . $e->getMessage());
            }
            return 0;
        }

        if (! file_exists($imagePath)) {
            $this->error("Image not found: {$imagePath}");
            return 1;
        }

        $manager = new OcrManager();
        
        if (! $manager->isAvailable()) {
            $this->error('No OCR engines available');
            return 1;
        }

        $this->info("Processing: {$imagePath}");
        $this->newLine();

        try {
            $engineOption = $this->option('engine');
            
            if ($engineOption) {
                $engine = $manager->using($engineOption);
                $this->info("Using engine: {$engine->getName()}");
                $text = $engine->extract($imagePath);
            } else {
                $result = $manager->extractDetailed($imagePath);
                $text = $result['text'];
                $this->info("Engine: {$result['engine']}");
                $this->info("Words: {$result['word_count']}, Chars: {$result['char_count']}");
            }

            $this->newLine();
            $this->info('Extracted Text:');
            $this->line(str_repeat('-', 50));
            $this->line($text);
            $this->line(str_repeat('-', 50));

            return 0;

        } catch (\Exception $e) {
            $this->error('OCR failed: ' . $e->getMessage());
            return 1;
        }
    }
}
