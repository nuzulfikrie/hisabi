# Deployment Guide

This guide covers deploying Hisabi to production environments.

## Requirements

- PHP 8.2+
- Composer 2.x
- MySQL 8.0+ or MariaDB 10.6+
- Nginx or Apache
- Redis (recommended)
- SSL certificate

## Deployment Checklist

### Pre-deployment

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate application key
- [ ] Configure database
- [ ] Setup mail/SMTP
- [ ] Configure queue driver (Redis recommended)
- [ ] Setup SSL certificate
- [ ] Configure backups

### Post-deployment

- [ ] Run migrations
- [ ] Seed initial data
- [ ] Setup cron jobs
- [ ] Configure queue workers
- [ ] Test all features
- [ ] Monitor error logs

## Server Setup

### Ubuntu 22.04 LTS

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql \
    php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl \
    php8.2-zip php8.2-gd php8.2-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Nginx
sudo apt install -y nginx

# Install Redis
sudo apt install -y redis-server

# Install MySQL
sudo apt install -y mysql-server
```

### Database Setup

```bash
# Secure MySQL
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE hisabi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hisabi'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON hisabi.* TO 'hisabi'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Application Deployment

### 1. Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/hisabi-app/hisabi.git
sudo chown -R www-data:www-data hisabi
```

### 2. Install Dependencies

```bash
cd hisabi
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 3. Environment Configuration

```bash
cp .env.example .env
sudo -u www-data php artisan key:generate
```

Edit `.env`:
```env
APP_NAME=Hisabi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=hisabi
DB_USERNAME=hisabi
DB_PASSWORD=strong_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1

TELEGRAM_BOT_TOKEN=your-token
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

### 4. Build Assets

```bash
npm ci
npm run build
```

### 5. Setup Storage

```bash
sudo -u www-data php artisan storage:link
sudo mkdir -p storage/framework/cache/data
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Run Migrations

```bash
sudo -u www-data php artisan migrate --force
```

## Web Server Configuration

### Nginx

Create `/etc/nginx/sites-available/hisabi`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/hisabi/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/hisabi /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### SSL with Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## Queue Workers

### Supervisor Configuration

Create `/etc/supervisor/conf.d/hisabi-worker.conf`:

```ini
[program:hisabi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hisabi/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hisabi/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hisabi-worker:*
```

## Scheduled Tasks

Add to crontab:
```bash
sudo crontab -e
```

```
* * * * * cd /var/www/hisabi && php artisan schedule:run >> /dev/null 2>&1
```

## OCR Service (Docker)

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Start PaddleOCR
cd /var/www/hisabi
docker-compose -f docker-compose.paddleocr.yml up -d
```

## Monitoring

### Log Rotation

Create `/etc/logrotate.d/hisabi`:

```
/var/www/hisabi/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    sharedscripts
}
```

### Health Check

```bash
# Add to monitoring system
curl -f https://yourdomain.com/health || echo "Site down"
```

## Backup Strategy

### Database

```bash
#!/bin/bash
# /opt/backup/backup-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p hisabi > /backup/hisabi_$DATE.sql
gzip /backup/hisabi_$DATE.sql

# Keep only last 7 days
find /backup -name "hisabi_*.sql.gz" -mtime +7 -delete
```

### Files

```bash
#!/bin/bash
# /opt/backup/backup-files.sh

DATE=$(date +%Y%m%d_%H%M%S)
tar -czf /backup/hisabi_files_$DATE.tar.gz /var/www/hisabi/storage/app
```

### Automated Backups

```bash
# Add to crontab
0 2 * * * /opt/backup/backup-db.sh
0 3 * * 0 /opt/backup/backup-files.sh
```

## Updates

### Zero-downtime Deployment

```bash
#!/bin/bash
# deploy.sh

cd /var/www/hisabi

# Maintenance mode
php artisan down --refresh=15

# Pull updates
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Restart queue workers
sudo supervisorctl restart hisabi-worker:*

# Exit maintenance mode
php artisan up
```

## Troubleshooting

### 500 Errors

```bash
sudo tail -f /var/www/hisabi/storage/logs/laravel.log
```

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/hisabi
sudo chmod -R 775 /var/www/hisabi/storage
```

### Queue Not Processing

```bash
sudo supervisorctl status hisabi-worker
sudo supervisorctl restart hisabi-worker:*
```
