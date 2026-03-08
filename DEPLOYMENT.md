# Hisabi Deployment Guide

## Architecture Overview

```
                    ┌─────────────┐
                    │   Nginx /   │
                    │  Caddy / LB │
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
        ┌─────┴─────┐ ┌───┴────┐ ┌────┴─────┐
        │  Laravel   │ │ Redis  │ │  MySQL   │
        │  PHP 8.4   │ │(cache, │ │  8.0+    │
        │  + Vite    │ │session,│ │          │
        └─────┬──────┘ │queue)  │ └──────────┘
              │        └────────┘
              │
     ┌────────┼─────────┐
     │                   │
┌────┴──────┐   ┌───────┴────────┐
│ PaddleOCR │   │ Telegram Bot   │
│ (optional)│   │ Webhook (ext.) │
└───────────┘   └────────────────┘
```

## Prerequisites

| Component | Version | Required |
|-----------|---------|----------|
| PHP | 8.4+ | Yes |
| MySQL | 8.0+ | Yes |
| Redis | 6.0+ | Yes |
| Node.js | 22+ | Build only |
| Composer | 2.x | Yes |
| Tesseract | 5.x | Optional (OCR) |
| Docker | 24+ | Optional (PaddleOCR) |

## Quick Deploy (Docker)

```bash
# Clone
git clone https://github.com/hisabi-app/hisabi.git
cd hisabi

# Configure environment
cp .env.example .env
# Edit .env with production values (see Environment section below)

# Build and start
docker compose up -d

# Inside container or after composer install
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force  # optional
```

## Manual Deploy (VPS / Bare Metal)

### 1. Server Setup

```bash
# Ubuntu/Debian
sudo apt update && sudo apt install -y \
  php8.4 php8.4-fpm php8.4-mysql php8.4-redis php8.4-mbstring \
  php8.4-xml php8.4-bcmath php8.4-curl php8.4-zip php8.4-gd \
  mysql-server redis-server nginx composer

# Optional: Tesseract for OCR
sudo apt install -y tesseract-ocr tesseract-ocr-eng tesseract-ocr-msa
```

### 2. Application Setup

```bash
cd /var/www
git clone https://github.com/hisabi-app/hisabi.git
cd hisabi

# Install PHP dependencies (no dev)
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node and build frontend
npm ci
npm run build

# Environment
cp .env.example .env
nano .env  # Configure all values (see below)

# Generate key
php artisan key:generate

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 3. Database

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE hisabi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'hisabi'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON hisabi.* TO 'hisabi'@'localhost'; FLUSH PRIVILEGES;"

# Run migrations
php artisan migrate --force

# Seed admin user (optional)
php artisan db:seed --class=AdminSeeder --force
```

### 4. Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/hisabi/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Max upload for OCR receipts
    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;  # OCR can be slow
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/hisabi /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 5. SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

## Environment Configuration

### Required `.env` values for production:

```env
APP_NAME=Hisabi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hisabi
DB_USERNAME=hisabi
DB_PASSWORD=SECURE_PASSWORD_HERE

# Redis (sessions + cache + queue)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (for password resets)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-mailgun-user
MAIL_PASSWORD=your-mailgun-pass
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Hisabi"

# Telegram Bot (optional)
TELEGRAM_BOT_TOKEN=your-bot-token-from-botfather
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
TELEGRAM_BOT_NAME=HisabiBot
TELEGRAM_WEBHOOK_SECRET=random-secret-string

# OCR (optional)
OCR_DEFAULT_ENGINE=auto
PADDLE_OCR_URL=http://localhost:8000
PADDLE_OCR_ENABLED=true
TESSERACT_PATH=tesseract
TESSERACT_LANG=eng+msa+ara
TESSERACT_ENABLED=true

# App defaults
HISABI_DEFAULT_CURRENCY=MYR
HISABI_DEFAULT_LOCALE=ms
```

## Optional Services

### PaddleOCR (Receipt Scanning)

For higher accuracy OCR (vs Tesseract fallback):

```bash
# From project root
docker compose -f docker-compose.paddleocr.yml up -d

# Verify it's running
curl http://localhost:8000/health
# Should return: {"status": "healthy"}
```

The app auto-detects available engines. If PaddleOCR is down, it falls back to Tesseract.

### Telegram Bot Setup

```bash
# 1. Create bot via @BotFather on Telegram
#    - /newbot → name it "HisabiBot"
#    - Copy the token to TELEGRAM_BOT_TOKEN in .env

# 2. Set webhook
php artisan telegram:set-webhook

# 3. Verify
php artisan telegram:set-webhook --info
```

For local development, use ngrok:
```bash
ngrok http 80
# Update TELEGRAM_WEBHOOK_URL in .env with the ngrok URL
php artisan telegram:set-webhook
```

### Queue Worker (for background jobs)

```bash
# Systemd service: /etc/systemd/system/hisabi-worker.service
```

```ini
[Unit]
Description=Hisabi Queue Worker
After=redis.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/hisabi
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable hisabi-worker
sudo systemctl start hisabi-worker
```

## Production Optimizations

```bash
# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer install --no-dev --optimize-autoloader

# Build frontend with production flags
npm run build
```

## CI/CD Pipeline

The project includes `.github/workflows/run-tests.yml` which runs on every push/PR to `main`:
- PHP 8.4 + SQLite (in-memory)
- Node 22 + frontend build
- Full test suite (480+ tests)

### Deployment via GitHub Actions (example)

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/hisabi
            git pull origin main
            composer install --no-dev --optimize-autoloader --no-interaction
            npm ci && npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            sudo systemctl reload php8.4-fpm
```

## Monitoring & Maintenance

### Health Checks

```bash
# App health
curl -s https://yourdomain.com | head -1

# OCR health
curl -s http://localhost:8000/health

# Queue status
php artisan queue:monitor redis:default --max=100
```

### Logs

```bash
# Application logs
tail -f /var/www/hisabi/storage/logs/laravel.log

# Nginx
tail -f /var/log/nginx/error.log

# Queue worker
journalctl -u hisabi-worker -f
```

### Backups

```bash
# Database backup (add to crontab)
mysqldump -u hisabi -p hisabi | gzip > /backups/hisabi-$(date +%Y%m%d).sql.gz

# Crontab entry (daily at 2 AM)
0 2 * * * mysqldump -u hisabi -pPASSWORD hisabi | gzip > /backups/hisabi-$(date +\%Y\%m\%d).sql.gz
```

### Laravel Scheduler

```bash
# Add to crontab
* * * * * cd /var/www/hisabi && php artisan schedule:run >> /dev/null 2>&1
```

## Update / Upgrade Procedure

```bash
cd /var/www/hisabi

# 1. Pull latest
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
php artisan queue:restart
sudo systemctl reload php8.4-fpm
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 500 error | Check `storage/logs/laravel.log`, ensure `storage/` is writable |
| Vite manifest not found | Run `npm run build` |
| Redis connection refused | Ensure Redis is running: `sudo systemctl start redis` |
| OCR timeout | Increase `PADDLE_OCR_TIMEOUT` or check PaddleOCR container |
| Telegram webhook fails | Verify SSL cert is valid, check `TELEGRAM_WEBHOOK_URL` |
| Session issues | Ensure Redis is running, check `SESSION_DRIVER=redis` |
| Permission denied | `sudo chown -R www-data:www-data storage bootstrap/cache` |

## Current Build Status

- **480 tests**, **1675 assertions**, 0 failures
- Phases 1-5 complete, Phase 6 pending
- PHP 8.4.15, Laravel 12, React 18, Tailwind v4
