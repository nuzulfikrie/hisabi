# Architecture

This document describes the high-level architecture of Hisabi.

## Overview

Hisabi follows a **modular monolith** architecture with:
- Domain-driven design principles
- Command Query Responsibility Segregation (CQRS) patterns
- Service layer for business logic
- Action classes for discrete operations

```
┌─────────────────────────────────────────────────────────────┐
│                        Presentation                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │   Web (SPA)  │  │  Telegram    │  │     API      │       │
│  │   (React)    │  │    Bot       │  │   (REST)     │       │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘       │
└─────────┼────────────────┼────────────────┼─────────────────┘
          │                │                │
┌─────────┼────────────────┼────────────────┼─────────────────┐
│         │    HTTP        │    Webhook     │   HTTP          │
│         └────────────────┴────────────────┘                 │
│                          │                                  │
│                   ┌──────┴──────┐                          │
│                   │   Routes    │                          │
│                   └──────┬──────┘                          │
│                          │                                  │
│  ┌───────────────────────┼───────────────────────────────┐ │
│  │                     Application                        │ │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐            │ │
│  │  │Controllers│  │ Middleware│  │  Requests │            │ │
│  │  └────┬─────┘  └──────────┘  └──────────┘            │ │
│  │       │                                              │ │
│  │  ┌────┴──────────────────────────────────────┐       │ │
│  │  │              Service Layer                 │       │ │
│  │  │  ┌────────┐  ┌────────┐  ┌────────┐       │       │ │
│  │  │  │Commands│  │ Queries│  │ Actions│       │       │ │
│  │  │  └────┬───┘  └───┬────┘  └────┬───┘       │       │ │
│  │  └───────┼──────────┼────────────┼───────────┘       │ │
│  └──────────┼──────────┼────────────┼───────────────────┘ │
│             │          │            │                     │
│  ┌──────────┼──────────┼────────────┼───────────────────┐ │
│  │          └──────────┴────────────┘                   │ │
│  │                      │                               │ │
│  │  ┌───────────────────┴─────────────────────────────┐ │ │
│  │  │                  Domain                         │ │ │
│  │  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐   │ │ │
│  │  │  │ Models │ │Services│ │Contracts│ │Events  │   │ │ │
│  │  │  └───┬────┘ └────┬───┘ └────┬───┘ └────┬───┘   │ │ │
│  │  └──────┼──────────┼──────────┼──────────┼────────┘ │ │
│  └─────────┼──────────┼──────────┼──────────┼──────────┘ │
│            │          │          │          │            │
│  ┌─────────┼──────────┼──────────┼──────────┼──────────┐ │
│  │         └──────────┴──────────┴──────────┘          │ │
│  │                      │                               │ │
│  │  ┌───────────────────┴─────────────────────────────┐ │ │
│  │  │              Infrastructure                      │ │ │
│  │  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐   │ │ │
│  │  │  │Database│ │  Cache │ │  Queue │ │ Storage│   │ │ │
│  │  │  │(MySQL) │ │(Redis) │ │(Redis) │ │ (S3)   │   │ │ │
│  │  │  └────────┘ └────────┘ └────────┘ └────────┘   │ │ │
│  │  └────────────────────────────────────────────────┘ │ │
│  └─────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────┘
```

## Directory Structure

```
app/
├── Actions/           # Laravel Actions (discrete operations)
│   └── Telegram/      # Telegram bot actions
├── BusinessLogic/     # Business logic services
├── Concerns/          # Reusable traits
├── Console/           # Artisan commands
│   └── Commands/      # Custom commands
├── Contracts/         # Interfaces
│   ├── Ocr/           # OCR contracts
│   └── Telegram/      # Telegram contracts
├── Domains/           # Domain modules
│   ├── Brand/         # Brand domain
│   ├── Category/      # Category domain
│   ├── Metrics/       # Metrics domain
│   ├── Sms/           # SMS parsing domain
│   └── Transaction/   # Transaction domain
├── Enums/             # PHP Enums
├── Exports/           # Excel/CSV exports
├── Http/              # HTTP layer
│   ├── Controllers/   # Controllers
│   ├── Middleware/    # Middleware
│   └── Requests/      # Form requests
├── Models/            # Eloquent models
├── Providers/         # Service providers
├── Services/          # Service layer
│   ├── Exports/       # Export services
│   ├── Ocr/           # OCR services
│   └── Telegram/      # Telegram services
└── Support/           # Support files (helpers)
```

## Key Patterns

### 1. Command Pattern

Used for write operations:

```php
// CreateTransactionCommand
class CreateTransactionCommand
{
    public function __construct(
        public float $amount,
        public int $brandId,
    ) {}
}

class CreateTransactionCommandHandler
{
    public function handle(CreateTransactionCommand $command): Transaction
    {
        return Transaction::create([
            'amount' => $command->amount,
            'brand_id' => $command->brandId,
        ]);
    }
}
```

### 2. Query Pattern

Used for read operations:

```php
class GetTransactionsQuery
{
    public function __construct(
        public ?string $startDate = null,
        public ?string $endDate = null,
    ) {}
}

class GetTransactionsQueryHandler
{
    public function handle(GetTransactionsQuery $query): Collection
    {
        return Transaction::query()
            ->when($query->startDate, fn ($q) => $q->whereDate('created_at', '>=', $query->startDate))
            ->get();
    }
}
```

### 3. Action Pattern (Laravel Actions)

For discrete, single-purpose operations:

```php
class ProcessReceiptImage
{
    use AsAction;
    
    public function handle(string $imagePath): array
    {
        // OCR processing
        return ['text' => $text, 'confidence' => $confidence];
    }
    
    // Can be run as job
    public function asJob(string $imagePath): void
    {
        $this->handle($imagePath);
    }
}

// Usage
ProcessReceiptImage::run('/path/to/image.jpg');
ProcessReceiptImage::dispatch('/path/to/image.jpg'); // As job
```

### 4. Strategy Pattern

For interchangeable algorithms:

```php
interface OcrEngine
{
    public function extract(string $imagePath): string;
}

class OcrManager
{
    private array $engines = [];
    
    public function register(OcrEngine $engine): void
    {
        $this->engines[] = $engine;
    }
    
    public function extract(string $imagePath): string
    {
        foreach ($this->engines as $engine) {
            if ($engine->isAvailable()) {
                return $engine->extract($imagePath);
            }
        }
        throw new \RuntimeException('No OCR engine available');
    }
}
```

## Domain Modules

Each domain is self-contained:

```
Domains/Transaction/
├── Models/
│   └── Transaction.php
├── Services/
│   └── TransactionService.php
└── Queries/
    └── GetTransactionsQuery/
```

## Data Flow

### Transaction Creation

```
User Input
    ↓
Controller (validation)
    ↓
Command (data transfer)
    ↓
Command Handler (business logic)
    ↓
Repository/Model (persistence)
    ↓
Event (side effects)
```

### Report Generation

```
Request
    ↓
Controller
    ↓
Query (filter specification)
    ↓
Query Handler (data retrieval)
    ↓
Report Builder (aggregation)
    ↓
Response
```

## Service Providers

```
AppServiceProvider      # General bindings
AuthServiceProvider     # Authorization
EventServiceProvider    # Events/listeners
RouteServiceProvider    # Routes
TelegramServiceProvider # Telegram bot
OcrServiceProvider      # OCR services
```

## External Services

```
┌────────────────────────────────────────┐
│           Hisabi Application           │
└────────────────┬───────────────────────┘
                 │
    ┌────────────┼────────────┬────────────┐
    │            │            │            │
    ▼            ▼            ▼            ▼
┌────────┐  ┌────────┐  ┌─────────┐  ┌──────────┐
│Telegram│  │ Paddle │  │OpenAI   │  │MySQL/    │
│  API   │  │  OCR   │  │  API    │  │MariaDB   │
└────────┘  └────────┘  └─────────┘  └──────────┘
```

## Security

- Authentication: Laravel Sanctum (API) + Sessions (Web)
- Authorization: Policy-based (UserPolicy, TransactionPolicy)
- CSRF: Enabled for web routes
- XSS: Blade escaping + Content Security Policy
- SQL Injection: Parameterized queries (Eloquent)

## Scalability

- **Horizontal**: Stateless application servers behind load balancer
- **Database**: Read replicas for queries
- **Cache**: Redis for sessions, cache, queues
- **Storage**: S3-compatible object storage for files
- **Queues**: Background job processing for OCR, exports

## Monitoring

- Logging: Laravel logs + structured logging
- Metrics: Custom metrics for business KPIs
- Health Checks: `/health` endpoint
- Queue Monitoring: Laravel Horizon (if enabled)
