# Troubleshooting Guide

Common issues and their solutions.

## Installation Issues

### Composer install fails

**Problem**: Memory limit or dependency conflicts

**Solution**:
```bash
# Increase memory limit
COMPOSER_MEMORY_LIMIT=-1 composer install

# Or use swap
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

### NPM build fails

**Problem**: Node version incompatibility

**Solution**:
```bash
# Check Node version
node -v  # Should be 18+

# Use nvm to switch
nvm install 18
nvm use 18
```

## Database Issues

### Migration fails

**Problem**: Existing tables or foreign key errors

**Solution**:
```bash
# Fresh install (WARNING: deletes data)
php artisan migrate:fresh --seed

# Or specific migration
php artisan migrate:rollback --step=1
php artisan migrate
```

### Connection refused

**Problem**: MySQL not running or wrong credentials

**Solution**:
```bash
# Check MySQL status
sudo systemctl status mysql

# Check credentials in .env
# Try connecting manually
mysql -u hisabi -p
```

## OCR Issues

### "OCR service unavailable"

**Problem**: PaddleOCR not running or Tesseract not installed

**Solution**:
```bash
# Check status
php artisan ocr:status

# Start PaddleOCR
docker-compose -f docker-compose.paddleocr.yml up -d

# Or install Tesseract
sudo apt install tesseract-ocr tesseract-ocr-eng tesseract-ocr-msa
```

### Low OCR accuracy

**Problem**: Poor image quality or wrong language

**Solution**:
- Ensure receipt is well-lit
- Use PaddleOCR for better accuracy
- Check language packs are installed
- Try preprocessing image (rotate, crop)

## Telegram Bot Issues

### Bot not responding

**Problem**: Webhook not set or token invalid

**Solution**:
```bash
# Check webhook
php artisan telegram:webhook:set

# Verify token in .env
# Test manually
curl https://api.telegram.org/bot<TOKEN>/getMe
```

### "User not linked" error

**Problem**: Account not connected to Telegram

**Solution**:
1. User sends `/link` to bot
2. Gets verification code
3. Enters code in web app: Settings → Telegram
4. Clicks "Link Account"

## Queue/Worker Issues

### Jobs not processing

**Problem**: Queue worker not running

**Solution**:
```bash
# Check status
sudo supervisorctl status hisabi-worker

# Restart
sudo supervisorctl restart hisabi-worker:*

# Check logs
sudo tail -f /var/log/supervisor/hisabi-worker.log
```

### Scheduled tasks not running

**Problem**: Cron not configured

**Solution**:
```bash
# Edit crontab
sudo crontab -e

# Add line
* * * * * cd /var/www/hisabi && php artisan schedule:run >> /dev/null 2>&1
```

## Performance Issues

### Slow page loads

**Problem**: No caching or database issues

**Solution**:
```bash
# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Use Redis
# In .env:
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### High memory usage

**Problem**: Large exports or queries

**Solution**:
- Use cursor for large datasets
- Increase PHP memory limit
- Queue large exports

```php
// In export class
public function collection()
{
    return Model::cursor(); // Not all()
}
```

## Error Logs

### Location

```
storage/logs/laravel.log
```

### View recent errors

```bash
tail -f storage/logs/laravel.log
```

### Enable debug mode (temporarily)

```bash
# In .env
APP_DEBUG=true

# Clear cache
php artisan config:clear
```

## Common Error Messages

### "419 Page Expired"

**Cause**: CSRF token mismatch

**Fix**: Refresh page or check SESSION_DOMAIN matches APP_URL

### "500 Server Error"

**Cause**: Various - check logs

**Fix**:
```bash
tail storage/logs/laravel.log
```

### "SQLSTATE[HY000] [2002] Connection refused"

**Cause**: Database server not running

**Fix**:
```bash
sudo systemctl start mysql
# or
sudo systemctl start mariadb
```

### "Trying to access array offset on value of type null"

**Cause**: Missing configuration or data

**Fix**: Check settings exist:
```bash
php artisan tinker
> \App\Models\Setting::get('app.name')
```

## Debug Commands

```bash
# Check application health
php artisan tinker --execute="echo 'OK'"

# List routes
php artisan route:list

# Check config
php artisan config:show app

# Test database
php artisan tinker --execute="\DB::select('SELECT 1');"

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan optimize
```

## Getting Help

1. Check logs: `storage/logs/laravel.log`
2. Search [GitHub Issues](https://github.com/hisabi-app/hisabi/issues)
3. Check documentation in `docs/`
4. Enable debug mode temporarily
5. Create issue with logs and steps to reproduce
