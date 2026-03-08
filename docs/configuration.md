# Configuration Guide

## Environment Variables

### Application

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | `hisabi` |
| `APP_ENV` | Environment (local/production) | `production` |
| `APP_DEBUG` | Enable debug mode | `false` |
| `APP_URL` | Application URL | `http://localhost` |

### Database

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `hisabi` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | `null` |

### Cache & Session

| Variable | Description | Default |
|----------|-------------|---------|
| `CACHE_DRIVER` | Cache driver | `file` |
| `SESSION_DRIVER` | Session driver | `file` |
| `QUEUE_CONNECTION` | Queue driver | `sync` |
| `REDIS_HOST` | Redis host | `127.0.0.1` |
| `REDIS_PASSWORD` | Redis password | `null` |
| `REDIS_PORT` | Redis port | `6379` |

### Telegram Bot

| Variable | Description | Default |
|----------|-------------|---------|
| `TELEGRAM_BOT_TOKEN` | Bot token from @BotFather | `null` |
| `TELEGRAM_WEBHOOK_URL` | Webhook endpoint URL | `null` |
| `TELEGRAM_BOT_NAME` | Bot display name | `HisabiBot` |

### OCR Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `PADDLE_OCR_URL` | PaddleOCR service URL | `http://localhost:8000` |
| `PADDLE_OCR_ENABLED` | Enable PaddleOCR | `true` |
| `TESSERACT_PATH` | Tesseract binary path | `tesseract` |
| `TESSERACT_LANG` | Tesseract languages | `eng+msa+ara` |
| `OCR_DEFAULT_ENGINE` | Default OCR engine | `auto` |

### OpenAI (Optional)

| Variable | Description | Default |
|----------|-------------|---------|
| `OPENAI_API_KEY` | OpenAI API key | `null` |

## Configuration Files

### config/hisabi.php

Main application configuration:

```php
return [
    'currency' => env('HISABI_CURRENCY', 'MYR'),
    'default_locale' => env('HISABI_LOCALE', 'en'),
    'items_per_page' => 20,
];
```

### config/ocr.php

OCR service configuration:

```php
return [
    'default' => env('OCR_DEFAULT_ENGINE', 'auto'),
    
    'paddle' => [
        'url' => env('PADDLE_OCR_URL', 'http://localhost:8000'),
        'timeout' => 120,
    ],
    
    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
        'lang' => env('TESSERACT_LANG', 'eng+msa+ara'),
    ],
];
```

### config/telegram.php

Telegram bot configuration:

```php
return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'bot_name' => env('TELEGRAM_BOT_NAME', 'HisabiBot'),
];
```

## Setting Up Telegram Bot

1. Message [@BotFather](https://t.me/BotFather) on Telegram
2. Create new bot with `/newbot`
3. Copy the token to `.env`:
   ```env
   TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
   TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
   ```
4. Set webhook:
   ```bash
   php artisan telegram:webhook:set
   ```

## Setting Up OCR

### Option 1: PaddleOCR (Docker)

```bash
docker-compose -f docker-compose.paddleocr.yml up -d
```

### Option 2: Tesseract (Local)

```bash
# Ubuntu/Debian
sudo apt install tesseract-ocr tesseract-ocr-eng tesseract-ocr-msa

# macOS
brew install tesseract
```

Verify installation:
```bash
php artisan ocr:status
```

## Language Configuration

Available locales:
- `en` - English
- `ms` - Malay (Bahasa Malaysia)

Set default in `.env`:
```env
APP_LOCALE=ms
```

Users can switch languages via UI or bot command.
