# Telegram Bot

Hisabi includes a powerful Telegram bot that allows users to track expenses and income via text messages and receipt photos.

## Features

- 💬 **Text Input**: Quick expense/income logging
- 📸 **Receipt Scanning**: OCR-powered receipt processing
- 🔐 **Account Linking**: Secure user verification
- 📊 **Statistics**: View financial summaries

## Setup

### 1. Create Bot

1. Message [@BotFather](https://t.me/BotFather)
2. Send `/newbot` and follow instructions
3. Save the bot token

### 2. Configure Environment

```env
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

### 3. Set Webhook

```bash
php artisan telegram:webhook:set
```

Verify webhook:
```bash
php artisan telegram:webhook:set --remove  # Remove if needed
```

## Commands

| Command | Description |
|---------|-------------|
| `/start` | Welcome message and instructions |
| `/help` | Show available commands |
| `/link` | Link Telegram to Hisabi account |
| `/stats` | Show financial statistics |
| `/ocr` | Check OCR service status |

## Text Message Formats

### Expense
```
expense 50 lunch at restaurant
-50 coffee
expense 25.50 grab ride
```

### Income
```
income 1000 salary
+500 freelance work
income 2000 bonus
```

## Receipt Photos

Simply send a photo of any receipt. The bot will:

1. Download the image
2. Extract text using OCR
3. Parse merchant, amount, and date
4. Show extracted details
5. Allow confirmation to save as transaction

**Supported formats**: JPG, PNG, PDF

## Account Linking

### Linking Process

1. User sends `/link` to bot
2. Bot generates verification code (valid 30 min)
3. User enters code in web app (Settings → Telegram)
4. Account is linked

### Unlinking

Users can unlink via web app:
- Go to Settings → Telegram
- Click "Unlink Account"

## Architecture

```
Telegram → Webhook → WebhookController
                    ↓
            ┌───────┴───────┐
            ↓               ↓
    Text Messages      Photos/Receipts
            ↓               ↓
    ParseTransaction   DownloadTelegramFile
            ↓               ↓
    CreateTransaction  ProcessReceiptImage
            ↓               ↓
    Transaction        OCR → Parsed Data
            ↓               ↓
    Confirmation       Create Transaction
```

## Security

- Webhook URL is secret (token in path)
- Verification codes expire in 30 minutes
- Users can only access their own data
- Photos are deleted after processing

## Troubleshooting

### Bot Not Responding

1. Check webhook is set:
   ```bash
   php artisan telegram:webhook:set
   ```

2. Verify token in `.env`

3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### OCR Not Working

```bash
php artisan ocr:status
```

Start OCR service if needed:
```bash
docker-compose -f docker-compose.paddleocr.yml up -d
```

### Account Linking Failed

- Ensure code hasn't expired (30 min limit)
- Check user is logged into web app
- Try generating new code with `/link`

## Customization

### Adding New Commands

Edit `app/Http/Controllers/Telegram/WebhookController.php`:

```php
private function handleCommand(string $text, string $chatId, ?string $username): void
{
    match ($command) {
        '/newcommand' => $this->handleNewCommand($chatId),
        // ...
    };
}

private function handleNewCommand(string $chatId): void
{
    Telegram::sendMessage([
        'chat_id' => $chatId,
        'text' => 'Custom response',
    ]);
}
```

## API Reference

See [Telegram Bot API](../api/telegram.md) for detailed API documentation.
