<?php

namespace Tests\Unit\Services\Ocr;

use App\Contracts\Ocr\OcrEngine;
use App\Services\Ocr\OcrManager;
use App\Services\Ocr\PaddleOcrService;
use App\Services\Ocr\TesseractOcrService;
use Tests\TestCase;

class OcrManagerTest extends TestCase
{
    public function test_it_returns_available_engines(): void
    {
        $manager = new OcrManager();
        $engines = $manager->getEngines();

        $this->assertIsArray($engines);
        // Depending on what's available in the test environment
        $this->assertContainsOnlyInstancesOf(OcrEngine::class, $engines);
    }

    public function test_it_checks_availability(): void
    {
        $manager = new OcrManager();

        // Availability depends on whether any engine is installed
        $isAvailable = $manager->isAvailable();
        $this->assertIsBool($isAvailable);
    }

    public function test_it_returns_active_engine_name(): void
    {
        $manager = new OcrManager();
        $engineName = $manager->getActiveEngineName();

        // Should return a string, either an engine name or 'None'
        $this->assertIsString($engineName);
    }

    public function test_it_falls_back_to_secondary_engine(): void
    {
        // Create a mock scenario where primary engine returns low quality result
        // This is an integration test that requires actual engines
        // For unit testing, we'd need to mock the engine registration

        $this->markTestSkipped(
            'Integration test requiring actual OCR engines. ' .
            'Primary engine must return <20 chars for fallback to trigger.'
        );
    }

    public function test_it_returns_empty_when_no_engines_available(): void
    {
        // In an environment where no OCR engines are available
        $manager = new OcrManager();

        if (! $manager->isAvailable()) {
            $this->assertEmpty($manager->getEngines());
            $this->assertEquals('None', $manager->getActiveEngineName());
        } else {
            $this->markTestSkipped('OCR engines are available in this environment');
        }
    }

    public function test_it_throws_exception_when_extracting_without_available_engines(): void
    {
        $manager = new OcrManager();

        if (! $manager->isAvailable()) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('No OCR engine available');

            $manager->extract('/path/to/image.jpg');
        } else {
            $this->markTestSkipped('OCR engines are available in this environment');
        }
    }

    public function test_extract_detailed_returns_expected_structure(): void
    {
        $manager = new OcrManager();

        if (! $manager->isAvailable()) {
            $this->markTestSkipped('No OCR engines available for testing');
        }

        // Create a temporary test image
        $tempFile = tempnam(sys_get_temp_dir(), 'ocr_test_');

        // Create a simple 100x100 white PNG image
        $image = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        imagepng($image, $tempFile);
        imagedestroy($image);

        try {
            $result = $manager->extractDetailed($tempFile);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('text', $result);
            $this->assertArrayHasKey('engine', $result);
            $this->assertArrayHasKey('word_count', $result);
            $this->assertArrayHasKey('char_count', $result);
            $this->assertIsString($result['text']);
            $this->assertIsString($result['engine']);
            $this->assertIsInt($result['word_count']);
            $this->assertIsInt($result['char_count']);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_it_allows_forcing_specific_engine(): void
    {
        $manager = new OcrManager();
        $engines = $manager->getEngines();

        if (empty($engines)) {
            $this->markTestSkipped('No OCR engines available for testing');
        }

        // Get first available engine name
        $engineName = $engines[0]->getName();

        $engine = $manager->using($engineName);

        $this->assertInstanceOf(OcrEngine::class, $engine);
        $this->assertEquals($engineName, $engine->getName());
    }

    public function test_using_throws_exception_for_invalid_engine(): void
    {
        $manager = new OcrManager();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("OCR engine 'NonExistent' not available");

        $manager->using('NonExistent');
    }
}
