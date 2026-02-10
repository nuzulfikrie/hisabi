# Installation Guide

## Table of Contents
- [Standard Installation](#standard-installation)
- [Docker Installation](#docker-installation)
- [Production Deployment](#production-deployment)

## Standard Installation

### Requirements

- PHP >= 8.2
- Composer
- MySQL >= 8.0 or MariaDB >= 10.6
- Redis (optional, for caching/sessions)
- Tesseract OCR (optional, for receipt scanning)

### Step-by-Step

1. **Clone Repository**
```bash
git clone https://github.com/hisabi-app/hisabi.git
cd hisabi
```

2. **Install Dependencies**
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

3. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Migration**
```bash
php artisan migrate --force
```

5. **Create Storage Link**
```bash
php artisan storage:link
```

6. **Set Permissions**
```bash
chmod -R 775 storage bootstrap/cache
```

## Docker Installation

### Using Docker Compose (Recommended)

```bash
# Clone repository
git clone https://github.com/hisabi-app/hisabi.git
cd hisabi

# Build and start services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Install dependencies
docker-compose exec app composer install
```

### Services Included

- `app` - Laravel application (PHP-FPM)
- `web` - Nginx web server
- `mysql` - MySQL database
- `redis` - Redis cache/session store
- `paddleocr` - OCR service (optional)

## Production Deployment

### Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Setup queue worker (Supervisor)
- [ ] Configure scheduled tasks (Cron)
- [ ] Enable HTTPS
- [ ] Configure CDN for assets (optional)

### Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hisabi
DB_USERNAME=hisabi
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_WEBHOOK_URL=https://your-domain.com/telegram/webhook
```

### Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/hisabi-worker.conf`:

```ini
[program:hisabi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hisabi/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hisabi/storage/logs/worker.log
```

### Scheduled Tasks

Add to crontab:
```bash
* * * * * cd /var/www/hisabi && php artisan schedule:run >> /dev/null 2>&1
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/hisabi/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Troubleshooting

### Common Issues

**Permission Denied on Storage**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Missing PHP Extensions**
```bash
# Ubuntu/Debian
sudo apt install php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-zip

# macOS with Homebrew
brew install php@8.2
```

**Database Connection Failed**
- Check `.env` database credentials
- Ensure MySQL is running
- Verify database exists: `CREATE DATABASE hisabi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
