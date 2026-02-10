# Telegram Bot API

Documentation for the Telegram bot webhook and message handling.

## Webhook Endpoint

```
POST /telegram/webhook
```

**Note**: No authentication required. Telegram uses token-based verification.

## Message Types

### Text Messages

Users can send expense/income in natural language:

```
expense 50 lunch at restaurant
income 1000 salary
-25 coffee
+500 freelance
```

### Photo Messages (Receipts)

Users can send receipt photos for OCR processing.

## Bot Commands

| Command | Description | Response |
|---------|-------------|----------|
| `/start` | Welcome message | Instructions + available commands |
| `/help` | Help message | Command reference |
| `/link` | Link account | Verification code generation |
| `/stats` | Show statistics | Income, expenses, balance |
| `/ocr` | OCR status | OCR service availability |

## Message Flow

### Text Input Flow

```
User sends: "expense 50 lunch"
    ↓
Telegram webhook
    ↓
ParseTransactionMessage Action
    ↓
CreateTransactionFromMessage Action
    ↓
Transaction created
    ↓
SendTransactionConfirmation
    ↓
User receives confirmation
```

### Photo Input Flow

```
User sends receipt photo
    ↓
Telegram webhook
    ↓
DownloadTelegramFile Action
    ↓
ProcessReceiptImage Action
    ↓
OCR extraction (PaddleOCR/Tesseract)
    ↓
Receipt parsing (merchant, amount, date)
    ↓
User receives parsed data
    ↓
User confirms → Transaction created
```

## Response Format

### Text Message Response

```
✅ Transaction Recorded

Brand: lunch
Amount: MYR 50.00
Category: EXPENSES
Date: 2024-02-10 14:30

View in app: /transactions
```

### Photo Message Response

```
🧾 Receipt Processed

Merchant: Restaurant ABC
Amount: MYR 45.50
Date: 10/02/2024
OCR Engine: PaddleOCR

To save this as a transaction, reply with:
expense 45.50 Restaurant ABC
```

## Error Responses

### Not Linked

```
Please link your account first. Use /link command to get started.
```

### OCR Failed

```
❌ Sorry, I couldn't process that receipt.

Error: OCR service unavailable

Please try:
• Sending a clearer photo
• Or type the details manually
```

### Parse Error

```
❌ Could not process your message. Please use format:

expense 50 lunch
or
income 1000 salary
```

## Account Linking Flow

### 1. Request Link

User sends `/link`

Bot responds:
```
To link your account:

1. Login to Hisabi web app
2. Go to Settings → Telegram
3. Enter this code: A1B2C3D4

Code expires in 30 minutes.
```

### 2. Verify in Web App

User enters code in Settings → Telegram

### 3. Link Established

User can now send transactions via Telegram.

## Security

- Verification codes expire in 30 minutes
- One-time use codes
- Users can unlink anytime via web app
- Webhook URL should be kept secret

## Rate Limiting

Telegram handles rate limiting on their end. The bot respects:
- 30 messages per second to same chat
- 20 messages per minute to same group

## Debugging

### Webhook Logs

```bash
tail -f storage/logs/laravel.log | grep Telegram
```

### Test Webhook

```bash
# Set webhook
curl -X POST https://api.telegram.org/bot<TOKEN>/setWebhook \
    -d "url=https://yourdomain.com/telegram/webhook"

# Get webhook info
curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo

# Delete webhook
curl https://api.telegram.org/bot<TOKEN>/deleteWebhook
```

## Customization

### Adding Commands

Edit `app/Http/Controllers/Telegram/WebhookController.php`:

```php
private function handleCommand(string $text, string $chatId, ?string $username): void
{
    match ($command) {
        '/custom' => $this->handleCustomCommand($chatId),
        // ...
    };
}

private function handleCustomCommand(string $chatId): void
{
    Telegram::sendMessage([
        'chat_id' => $chatId,
        'text' => 'Custom response here',
    ]);
}
```

## See Also

- [Telegram Bot Feature](../features/telegram-bot.md)
- [OCR Feature](../features/ocr.md)
- [Telegram Bot API Docs](https://core.telegram.org/bots/api)
