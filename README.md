<h1 align="center">
<img width="300" src="./public/images/logo.svg" />    
</h1>

<p align="center">
  <b>Hisabi is a simple yet powerful, self-hosted personal finance tracking web app with SMS parsing, AI insights, Telegram bot, and OCR receipt scanning!</b>
</p>

<p align="center"><a href="https://www.youtube.com/watch?v=kfwcMdlFn9o&list=PLw5MK6ws-o1_rNobmZCmnH5G11vwCiKKk&ab_channel=ILoveMathAcademy" target="__blank"><img src="https://raw.githubusercontent.com/hisabi-app/hisabi/refs/heads/main/public/images/showcase.png" /></a></p>

## 🚀 What's New

### Latest Features

- **📱 Telegram Bot** - Track expenses via text messages or receipt photos
- **🧾 OCR Receipt Scanning** - Extract text from receipts using PaddleOCR
- **👥 User Management** - Multi-user support with roles (Admin, User, Accountant)
- **📊 Excel/CSV Export** - Export transactions and reports
- **🌐 Multi-language** - English and Malay (Bahasa Malaysia) support
- **⚙️ Settings System** - Global and per-user configuration
- **🔐 Session Management** - Track and manage active sessions

## 🛠 Features

- [x] 🔐 Self-hosted — Full control over your data
- [x] 📩 SMS Parser — Auto-detect bank transactions
- [x] 📸 OCR Receipt Scanning — Extract data from receipts (PaddleOCR)
- [x] 🤖 Telegram Bot — Quick expense tracking via messages
- [x] 📊 Reports & Visualization — Clear finance insights
- [x] 🤖 HisabAI — AI-powered finance assistance
- [x] 👥 User Management — Multi-user with role-based access
- [x] 🌐 Multi-language — English & Malay support
- [x] 📥 Excel/CSV Export — Export your data anytime
- [x] 🆓 MIT Licensed — Fully open-source

## 📚 Documentation

- [Getting Started](docs/getting-started.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Telegram Bot Setup](docs/features/telegram-bot.md)
- [OCR Setup](OCR_SETUP.md)
- [API Reference](docs/api/README.md)

## 🎮 Demo

Try the app with [live demo](https://hisabi.on-forge.com/).

## ▶️ Quick Start

### Docker Installation (Recommended)

```bash
git clone https://github.com/hisabi-app/hisabi && cd hisabi

make build    # Build the docker image
make run      # Start services
make install  # First-time setup
```

Visit `http://localhost`

### Manual Installation

```bash
# Clone repository
git clone https://github.com/hisabi-app/hisabi.git
cd hisabi

# Install dependencies
composer install
npm install && npm run build

# Setup environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Start server
php artisan serve
```

## 🤖 Telegram Bot Setup

1. Message [@BotFather](https://t.me/BotFather) to create a bot
2. Copy token to `.env`:
   ```env
   TELEGRAM_BOT_TOKEN=your-bot-token
   TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
   ```
3. Set webhook:
   ```bash
   php artisan telegram:webhook:set
   ```

## 🧾 OCR Setup (Optional)

For receipt scanning via Telegram:

```bash
# Start PaddleOCR service
docker-compose -f docker-compose.paddleocr.yml up -d

# Or install Tesseract
sudo apt install tesseract-ocr tesseract-ocr-eng tesseract-ocr-msa

# Check status
php artisan ocr:status
```

## 📸 Screenshots

*Coming soon: Telegram bot demo, OCR in action*

## 💰 Sponsors

Support this project by becoming a sponsor ❤️. Your logo will show up here with a link to your website. [Become a sponsor](https://github.com/sponsors/saleem-hadad)

Follow me on [LinkedIn](https://www.linkedin.com/in/saleem-hadad/) for updates and latest news.

## 🏗️ Architecture

Hisabi follows a modular monolith architecture with:
- **Domain-Driven Design** — Organized by business domains
- **Laravel Actions** — Single-purpose action classes
- **Service Layer** — Business logic encapsulation
- **Strategy Pattern** — Swappable OCR engines

See [Architecture Documentation](docs/architecture.md)

## 🛡️ Security

- Authentication via Laravel Sanctum
- CSRF protection for web routes
- SQL injection prevention via Eloquent
- XSS protection via Blade escaping
- Session security with Redis option

## 🚢 Deployment

See [Deployment Guide](docs/deployment.md) for:
- Production server setup
- SSL configuration
- Queue workers
- Scheduled tasks
- Zero-downtime updates

## 🐛 Troubleshooting

See [Troubleshooting Guide](docs/troubleshooting.md) for common issues and solutions.

Quick checks:
```bash
php artisan ocr:status         # Check OCR
php artisan telegram:webhook:set  # Check Telegram
php artisan tinker --execute="echo 'OK'"  # App health
```

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) — The PHP framework
- [React](https://reactjs.org) — Frontend library
- [PaddleOCR](https://github.com/PaddlePaddle/PaddleOCR) — OCR engine
- [Maatwebsite Excel](https://github.com/SpartnerNL/Laravel-Excel) — Excel export
- [Laravel Actions](https://github.com/lorisleiva/laravel-actions) — Action pattern

## JetBrains Sponsorship

Thank you, JetBrains for sponsoring the license ❤️

<a href="https://www.jetbrains.com/community/opensource/#support" target="__blank">
<img src="https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.png?_gl=1*18f1z4q*_ga*MTI4MDYwODYzNy4xNjUyMzU3ODM3*_ga_9J976DJZ68*MTY2MTg3NDM2NC4xMi4xLjE2NjE4NzUxNTAuMC4wLjA.&_ga=2.85008921.1685901777.1661797034-1280608637.1652357837" width="250px" />
</a>

## Get $200 DigitalOcean Credit

[![DigitalOcean Referral Badge](https://web-platforms.sfo2.cdn.digitaloceanspaces.com/WWW/Badge%201.svg)](https://www.digitalocean.com/?refcode=64aee93d49da&utm_campaign=Referral_Invite&utm_medium=Referral_Program&utm_source=badge)

---

<p align="center">
  <a href="https://github.com/hisabi-app/hisabi/stargazers">
    <img alt="GitHub stars" src="https://img.shields.io/github/stars/hisabi-app/hisabi?style=social">
  </a>
  <a href="https://github.com/hisabi-app/hisabi/network/members">
    <img alt="GitHub forks" src="https://img.shields.io/github/forks/hisabi-app/hisabi?style=social">
  </a>
</p>
