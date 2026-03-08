# API Documentation

Hisabi provides a RESTful API for integration with external services.

## Authentication

All API requests require authentication via Laravel Sanctum.

### Obtaining Token

```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

Response:
```json
{
    "token": "1|laravel_sanctum_token..."
}
```

### Using Token

```http
GET /api/v1/transactions
Authorization: Bearer 1|laravel_sanctum_token...
```

## Endpoints

### Transactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/transactions` | List transactions |
| POST | `/api/v1/transactions` | Create transaction |
| PUT | `/api/v1/transactions/{id}` | Update transaction |
| DELETE | `/api/v1/transactions/{id}` | Delete transaction |

### Brands

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/brands` | List brands |
| GET | `/api/v1/brands/all` | All brands (unpaginated) |
| POST | `/api/v1/brands` | Create brand |
| PUT | `/api/v1/brands/{id}` | Update brand |
| DELETE | `/api/v1/brands/{id}` | Delete brand |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/categories/all` | All categories |
| POST | `/api/v1/categories` | Create category |
| PUT | `/api/v1/categories/{id}` | Update category |
| DELETE | `/api/v1/categories/{id}` | Delete category |

### Metrics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/metrics/total-income` | Total income |
| GET | `/api/v1/metrics/total-expenses` | Total expenses |
| GET | `/api/v1/metrics/net-worth` | Net worth |
| GET | `/api/v1/metrics/category-trend` | Category trend |
| GET | `/api/v1/metrics/brand-trend` | Brand trend |

See [Metrics API](metrics.md) for full list.

### AI

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/ai/chat` | Chat with HisabAI |

### User

| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/api/v1/user/profile` | Update profile |

## Response Format

All responses are JSON:

```json
{
    "data": {},
    "message": "Success",
    "success": true
}
```

Error response:

```json
{
    "message": "Error message",
    "errors": {
        "field": ["Error details"]
    },
    "success": false
}
```

## Rate Limiting

API requests are limited to 60 per minute per user.

## Pagination

List endpoints support pagination:

```http
GET /api/v1/transactions?page=2&per_page=50
```

Response includes:

```json
{
    "data": [...],
    "links": {
        "first": "...",
        "last": "...",
        "prev": "...",
        "next": "..."
    },
    "meta": {
        "current_page": 2,
        "from": 16,
        "last_page": 10,
        "per_page": 15,
        "to": 30,
        "total": 150
    }
}
```

## Error Codes

| Code | Description |
|------|-------------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |

## Webhooks

### Telegram Webhook

```http
POST /telegram/webhook
```

No authentication required (uses token verification).

See [Telegram Bot](../features/telegram-bot.md) for details.

## SDKs

Official SDKs coming soon:
- PHP
- JavaScript/TypeScript
- Python
