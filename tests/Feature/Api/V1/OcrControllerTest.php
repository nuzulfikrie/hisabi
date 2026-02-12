<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\Ocr\OcrManager;
use App\Services\Ocr\ReceiptParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OcrControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_it_requires_authentication_for_scan(): void
    {
        $response = $this->postJson('/api/v1/ocr/scan', [
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertUnauthorized();
    }

    public function test_it_requires_authentication_for_scan_and_parse(): void
    {
        $response = $this->postJson('/api/v1/ocr/scan-and-parse', [
            'image' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertUnauthorized();
    }

    public function test_it_requires_authentication_for_status(): void
    {
        $response = $this->getJson('/api/v1/ocr/status');

        $response->assertUnauthorized();
    }

    public function test_it_scans_an_uploaded_image(): void
    {
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('extractDetailed')->once()->andReturn([
                'text' => "LOTUS'S\n12 Feb 2024\nTOTAL: RM 45.50",
                'engine' => 'PaddleOCR',
                'word_count' => 10,
                'char_count' => 50,
            ]);
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'text',
                    'engine',
                    'word_count',
                    'char_count',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.engine', 'PaddleOCR');
    }

    public function test_it_returns_parsed_receipt_data(): void
    {
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('extractDetailed')->once()->andReturn([
                'text' => "LOTUS'S\n12 Feb 2024\nTOTAL: RM 45.50",
                'engine' => 'PaddleOCR',
                'word_count' => 10,
                'char_count' => 50,
            ]);
        });

        $this->mock(ReceiptParser::class, function ($mock) {
            $mock->shouldReceive('parse')->once()->andReturn([
                'merchant' => "LOTUS'S",
                'amount' => 45.50,
                'date' => '2024-02-12',
                'items' => [
                    ['description' => 'Item 1', 'price' => 20.00, 'quantity' => 1],
                    ['description' => 'Item 2', 'price' => 25.50, 'quantity' => 1],
                ],
                'raw_text' => "LOTUS'S\n12 Feb 2024\nTOTAL: RM 45.50",
            ]);
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan-and-parse', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'parsed' => [
                        'merchant',
                        'amount',
                        'date',
                        'items',
                    ],
                    'raw' => [
                        'text',
                        'engine',
                        'word_count',
                        'char_count',
                    ],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parsed.merchant', "LOTUS'S")
            ->assertJsonPath('data.parsed.amount', 45.50)
            ->assertJsonPath('data.parsed.date', '2024-02-12');
    }

    public function test_it_returns_ocr_status(): void
    {
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('getActiveEngineName')->once()->andReturn('PaddleOCR');
            $mock->shouldReceive('getEngines')->once()->andReturn([
                new class {
                    public function getName(): string { return 'PaddleOCR'; }
                    public function isAvailable(): bool { return true; }
                },
                new class {
                    public function getName(): string { return 'Tesseract'; }
                    public function isAvailable(): bool { return true; }
                },
            ]);
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/ocr/status');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'available',
                    'active_engine',
                    'engines',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.active_engine', 'PaddleOCR');
    }

    public function test_it_validates_image_upload_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_it_validates_image_mime_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_it_validates_image_size(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('large.jpg')->size(11 * 1024), // 11MB
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_it_rate_limits_scan_requests(): void
    {
        // Mock OCR manager to avoid actual processing
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('extractDetailed')->andReturn([
                'text' => 'Test',
                'engine' => 'Test',
                'word_count' => 1,
                'char_count' => 4,
            ]);
        });

        // Make 10 successful requests
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->postJson('/api/v1/ocr/scan', [
                    'image' => UploadedFile::fake()->image('receipt.jpg'),
                ])
                ->assertOk();
        }

        // 11th request should be rate limited
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'message',
                'retry_after',
            ])
            ->assertHeader('Retry-After')
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining', '0');
    }

    public function test_it_handles_ocr_engine_failure_gracefully(): void
    {
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturn(true);
            $mock->shouldReceive('extractDetailed')->once()->andThrow(
                new \RuntimeException('OCR engine failed')
            );
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Failed to process image: OCR engine failed');
    }

    public function test_it_returns_503_when_ocr_not_available(): void
    {
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->once()->andReturn(false);
        });

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'OCR service not available. Please try again later.');
    }

    public function test_rate_limit_is_per_user(): void
    {
        // Create another user
        $otherUser = User::factory()->create();

        // Mock OCR manager
        $this->mock(OcrManager::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('extractDetailed')->andReturn([
                'text' => 'Test',
                'engine' => 'Test',
                'word_count' => 1,
                'char_count' => 4,
            ]);
        });

        // Make 10 requests as first user
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->user)
                ->postJson('/api/v1/ocr/scan', [
                    'image' => UploadedFile::fake()->image('receipt.jpg'),
                ])
                ->assertOk();
        }

        // First user is rate limited
        $this->actingAs($this->user)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertStatus(429);

        // Second user should still be able to make requests
        $this->actingAs($otherUser)
            ->postJson('/api/v1/ocr/scan', [
                'image' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertOk();
    }
}
