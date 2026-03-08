# OCR Receipt Scanning

Hisabi uses Optical Character Recognition (OCR) to extract text from receipt photos sent via Telegram.

## Overview

```
User sends photo → Telegram Bot → Download → OCR → Parse → Transaction
```

## OCR Engines

### 1. PaddleOCR (Recommended)

**Best for**: Mixed languages, noisy photos, complex layouts

**Setup**:
```bash
docker-compose -f docker-compose.paddleocr.yml up -d
```

**Features**:
- High accuracy for English, Malay, Arabic
- Layout-aware text detection
- Angle correction for rotated images

### 2. Tesseract (Fallback)

**Best for**: Simple text, offline use, quick setup

**Setup**:
```bash
# Ubuntu/Debian
sudo apt install tesseract-ocr tesseract-ocr-eng tesseract-ocr-msa

# macOS
brew install tesseract
```

**Features**:
- Fast processing
- No Docker required
- Multiple language packs available

## Architecture

```
┌─────────────────┐
│  Receipt Photo  │
└────────┬────────┘
         ↓
┌─────────────────┐
│ DownloadTelegram│
│   File Action   │
└────────┬────────┘
         ↓
┌─────────────────┐
│   OCR Manager   │ ← Strategy Pattern
│  ├─ PaddleOCR   │
│  └─ Tesseract   │
└────────┬────────┘
         ↓
┌─────────────────┐
│ProcessReceipt   │
│  Image Action   │
└────────┬────────┘
         ↓
┌─────────────────┐
│  Parsed Data    │
│ (merchant, amt) │
└─────────────────┘
```

## Configuration

### Environment Variables

```env
# PaddleOCR
PADDLE_OCR_URL=http://localhost:8000
PADDLE_OCR_ENABLED=true

# Tesseract
TESSERACT_PATH=tesseract
TESSERACT_LANG=eng+msa+ara
TESSERACT_ENABLED=true

# Default behavior
OCR_DEFAULT_ENGINE=auto  # auto, paddle, tesseract
```

### Config File

`config/ocr.php`:

```php
return [
    'default' => 'auto',
    
    'paddle' => [
        'url' => 'http://localhost:8000',
        'timeout' => 120,
    ],
    
    'tesseract' => [
        'path' => 'tesseract',
        'lang' => 'eng+msa+ara',
    ],
    
    'thresholds' => [
        'min_text_length' => 10,
        'fallback_threshold' => 20,
    ],
];
```

## Usage

### Check Status

```bash
php artisan ocr:status
```

### Test OCR

```bash
# Test with image
php artisan ocr:test /path/to/receipt.jpg

# Force specific engine
php artisan ocr:test /path/to/receipt.jpg --engine=tesseract
```

### Laravel Usage

```php
use App\Services\Ocr\OcrManager;

// Auto-select best engine
$manager = app(OcrManager::class);
$text = $manager->extract('/path/to/image.jpg');

// With metadata
$result = $manager->extractDetailed('/path/to/image.jpg');
// Returns: ['text' => '...', 'engine' => 'PaddleOCR', 'word_count' => 42]

// Force specific engine
$paddle = app(\App\Services\Ocr\PaddleOcrService::class);
$text = $paddle->extract('/path/to/image.jpg');
```

## Receipt Parsing

The OCR service attempts to extract:

| Field | Pattern Examples |
|-------|------------------|
| Merchant | First few lines of text |
| Amount | `RM 50.00`, `TOTAL: 50.00`, `50.00` |
| Date | `25/12/2024`, `2024-12-25`, `25 Dec 2024` |
| Items | Line items (if detected) |

## Supported Languages

### PaddleOCR
- English (en)
- Malay (ms)
- Arabic (ar)
- Chinese (ch)
- Japanese (jp)
- Korean (kr)
- And more...

### Tesseract
Language packs can be installed:
```bash
# English + Malay + Arabic
sudo apt install tesseract-ocr-eng tesseract-ocr-msa tesseract-ocr-ara

# Chinese
sudo apt install tesseract-ocr-chi-sim tesseract-ocr-chi-tra

# Japanese
sudo apt install tesseract-ocr-jpn
```

## Performance

| Engine | Speed | Accuracy | Setup |
|--------|-------|----------|-------|
| PaddleOCR | ~2-5s | ⭐⭐⭐⭐⭐ | Docker |
| Tesseract | ~0.5-2s | ⭐⭐⭐ | Local |

## Troubleshooting

### "OCR service unavailable"

Start PaddleOCR:
```bash
docker-compose -f docker-compose.paddleocr.yml up -d
```

Or install Tesseract:
```bash
sudo apt install tesseract-ocr
```

### Low accuracy results

1. **Image quality**: Ensure photo is clear, well-lit
2. **Correct language**: Verify language packs are installed
3. **Try PaddleOCR**: More accurate for complex receipts
4. **Preprocessing**: Image is auto-converted to RGB

### Docker issues

```bash
# Check container status
docker ps | grep paddleocr

# View logs
docker logs hisabi-paddleocr

# Restart
docker-compose -f docker-compose.paddleocr.yml restart
```

## Adding Custom OCR Engine

1. Implement interface:

```php
use App\Contracts\Ocr\OcrEngine;

class MyOcrService implements OcrEngine
{
    public function extract(string $imagePath): string
    {
        // Your implementation
    }
    
    public function isAvailable(): bool
    {
        return true;
    }
    
    public function getName(): string
    {
        return 'MyOCR';
    }
}
```

2. Register in `OcrManager`:

```php
// In OcrManager::registerEngines()
$myOcr = new MyOcrService();
if ($myOcr->isAvailable()) {
    $this->engines[] = $myOcr;
}
```

## See Also

- [OCR Setup Guide](../../OCR_SETUP.md)
- [Telegram Bot](telegram-bot.md)
