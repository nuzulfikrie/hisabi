# Changelog

All notable changes to Hisabi will be documented in this file.

## [Unreleased]

### Added
- **User Management System**
  - User roles (Admin, User, Accountant)
  - User status management (Active, Inactive, Suspended)
  - Admin user management interface
  - Telegram account linking for users

- **Session Management**
  - Active session tracking
  - Session termination (individual and all)
  - Query session service for filter preservation

- **Excel/CSV Export**
  - Maatwebsite Excel integration
  - Transactions export with filters
  - Reports export
  - Streaming exports for large datasets

- **Telegram Bot Integration**
  - Webhook-based bot architecture
  - Text message transaction creation
  - Account linking with verification codes
  - Statistics command
  - Laravel Actions pattern for operations

- **OCR Receipt Scanning**
  - PaddleOCR Docker service
  - Tesseract OCR fallback
  - Strategy pattern for multiple engines
  - Receipt photo processing via Telegram
  - Merchant, amount, date extraction

- **Settings System**
  - Global application settings
  - Per-user settings with type casting
  - Settings management interface

- **Localization**
  - Multi-language support (English, Malay)
  - Locale middleware
  - Language switching

- **Support Helper Functions**
  - Setting helpers
  - User role helpers
  - Export helpers
  - Telegram helpers
  - Locale helpers

### Changed
- Updated User model with new traits and fields
- Enhanced Enums with InteractsWithEnum trait
- Refactored code following UMS patterns

### Technical
- Added packages:
  - `maatwebsite/excel` - Excel export
  - `lorisleiva/laravel-actions` - Action classes
  - `irazasyed/telegram-bot-sdk` - Telegram Bot API
- Implemented Concerns (traits) pattern
- Created Service Layer architecture
- Added Contract interfaces

## [2.0.0] - Previous Release

### Features
- SMS transaction parsing
- Financial reports and metrics
- AI-powered insights (HisabAI)
- Brand and category management
- Budget tracking
- Docker support

---

For detailed implementation information, see:
- [Implementation Plan](IMPLEMENTATION_PLAN.md)
- [Documentation](docs/README.md)
- [OCR Setup](OCR_SETUP.md)
