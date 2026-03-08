# Getting Started with Hisabi

Hisabi is a self-hosted personal finance tracking application with AI-powered insights, SMS parsing, Telegram bot integration, and receipt OCR capabilities.

## Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+ or MariaDB 10.6+
- Node.js 18+ (for frontend builds)
- Docker (optional, for OCR services)

## Quick Start

### 1. Clone and Install

```bash
git clone https://github.com/hisabi-app/hisabi.git
cd hisabi

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Build frontend assets
npm run build
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:
```env
DB_DATABASE=hisabi
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 4. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## Default Credentials

After seeding, you can log in with:
- Email: `admin@example.com`
- Password: `password`

## Next Steps

- [Configure Telegram Bot](features/telegram-bot.md)
- [Setup OCR for Receipt Scanning](../OCR_SETUP.md)
- [Customize Settings](features/settings.md)
