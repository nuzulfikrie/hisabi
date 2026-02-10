# OCR Setup Guide for Hisabi

This guide explains how to set up OCR (Optical Character Recognition) for receipt scanning in the Hisabi Telegram bot.

## Architecture

```
Telegram Bot
   ↓
Laravel Webhook
   ↓
OCR Manager (Strategy Pattern)
   ├─ PaddleOCR (primary, Docker) → Best accuracy
   └─ Tesseract (fallback, local) → No Docker needed
   ↓
Extracted Text → Transaction Creation
```

## Option 1: PaddleOCR (Recommended)

Best accuracy for mixed languages (English, Malay, Arabic) and noisy receipts.

### Start PaddleOCR Service

```bash
# Using Docker Compose
docker-compose -f docker-compose.paddleocr.yml up -d

# Or with docker run
docker run -d \
  --name hisabi-paddleocr \
  -p 8000:8000 \
  --restart unless-stopped \
  ghcr.io/paddlepaddle/paddleocr:latest
```

### Verify Installation

```bash
# Check health
curl http://localhost:8000/health

# Test with sample image
curl -X POST -F "image=@/path/to/receipt.jpg" http://localhost:8000/ocr
```

### Laravel Configuration

Add to `.env`:
```env
PADDLE_OCR_URL=http://localhost:8000
PADDLE_OCR_ENABLED=true
```

## Option 2: Tesseract (Local)

Good accuracy, no Docker required. Works offline.

### Install Tesseract

**Ubuntu/Debian:**
```bash
sudo apt install tesseract-ocr \
                 tesseract-ocr-eng \
                 tesseract-ocr-msa \
                 tesseract-ocr-ara
```

**macOS:**
```bash
brew install tesseract
```

**Windows:**
Download from: https://github.com/UB-Mannheim/tesseract/wiki

### Install Language Packs

```bash
# List available languages
tesseract --list-langs

# Install additional (Ubuntu)
sudo apt install tesseract-ocr-chi-sim  # Chinese
sudo apt install tesseract-ocr-jpn      # Japanese
```

### Laravel Configuration

Add to `.env`:
```env
TESSERACT_PATH=tesseract
TESSERACT_LANG=eng+msa+ara
TESSERACT_ENABLED=true
```

## Usage

### Check Status

```bash
php artisan ocr:status
```

### Test OCR

```bash
# Test with an image
php artisan ocr:test /path/to/receipt.jpg

# Force specific engine
php artisan ocr:test /path/to/receipt.jpg --engine=tesseract
```

### Telegram Bot

Once OCR is configured, users can simply send receipt photos to the bot:

1. User sends receipt photo
2. Bot downloads the image
3. OCR extracts text
4. Parsed data is shown to user
5. User confirms to create transaction

## Laravel Integration

### Service Container

```php
// Get OCR Manager (auto-selects best engine)
$manager = app(\App\Services\Ocr\OcrManager::class);
$text = $manager->extract('/path/to/image.jpg');

// Force specific engine
$paddle = app(\App\Services\Ocr\PaddleOcrService::class);
$text = $paddle->extract('/path/to/image.jpg');
```

### In Controllers

```php
use App\Services\Ocr\OcrManager;

public function scan(Request $request, OcrManager $ocr)
{
    $path = $request->file('receipt')->store('temp');
    $text = $ocr->extract(storage_path('app/' . $path));
    
    return response()->json(['text' => $text]);
}
```

## Configuration Options

Edit `config/ocr.php`:

```php
return [
    'default' => 'auto',           // auto, paddle, tesseract
    
    'paddle' => [
        'url' => 'http://localhost:8000',
        'timeout' => 120,
    ],
    
    'tesseract' => [
        'path' => 'tesseract',
        'lang' => 'eng+msa+ara',   // Language packs
    ],
    
    'thresholds' => [
        'min_text_length' => 10,
        'fallback_threshold' => 20,
    ],
];
```

## Troubleshooting

### PaddleOCR Connection Refused

```bash
# Check if container is running
docker ps | grep paddleocr

# Check logs
docker logs hisabi-paddleocr

# Restart
docker-compose -f docker-compose.paddleocr.yml restart
```

### Tesseract Not Found

```bash
# Find tesseract binary
which tesseract

# Update .env
TESSERACT_PATH=/usr/bin/tesseract
```

### Low Quality Results

1. Ensure image is clear and well-lit
2. Check if correct language packs are installed
3. Try preprocessing: convert to grayscale, increase contrast
4. Use PaddleOCR for better accuracy

## Performance

| Engine | Speed | Accuracy | Setup |
|--------|-------|----------|-------|
| PaddleOCR | Medium | ⭐⭐⭐⭐⭐ | Docker required |
| Tesseract | Fast | ⭐⭐⭐ | Local install |

## Security

- Temporary images are stored in `storage/app/temp/ocr/`
- Files are deleted after processing
- Validate file types before OCR (jpg, png, pdf)
- Limit file size (recommended: max 10MB)

## API Reference

### PaddleOCR Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Health check |
| `/ocr` | POST | Extract text from image |
| `/ocr/base64` | POST | Extract from base64 image |

## License

Both PaddleOCR and Tesseract are open-source and free to use commercially.
