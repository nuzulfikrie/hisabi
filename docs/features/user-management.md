# User Management

Hisabi includes a comprehensive user management system for administrators.

## Features

- User CRUD operations
- Role-based access (Admin, User, Accountant)
- Account status management (Active, Inactive, Suspended)
- Telegram account linking
- Session management

## User Roles

| Role | Permissions |
|------|-------------|
| `admin` | Full system access |
| `user` | Standard user access |
| `accountant` | Financial reports access |

## User Status

| Status | Description |
|--------|-------------|
| `active` | Can log in and use system |
| `inactive` | Cannot log in |
| `suspended` | Temporarily blocked |

## Admin Interface

Access user management at `/admin/users`

### Features

- **List Users**: Paginated table with search and filters
- **Create User**: Add new users manually
- **Edit User**: Modify user details
- **Toggle Status**: Activate/deactivate accounts
- **Disconnect Telegram**: Unlink Telegram accounts

### Search & Filter

Available filters:
- Name (LIKE search)
- Email (LIKE search)
- Status (equals)
- Role (equals)

## User Model

```php
use App\Enums\UserRole;
use App\Enums\UserStatus;

$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
    'role' => UserRole::USER,
    'status' => UserStatus::ACTIVE,
    'locale' => 'en',
]);

// Check role
$user->isAdmin();           // true/false
$user->hasAdminRole();      // true/false

// Check status
$user->isActive();          // true/false

// Telegram
$user->hasTelegramLinked(); // true/false
$user->generateTelegramVerificationCode(); // Returns 8-char code
```

## Traits

### InteractsWithRole

```php
use App\Concerns\InteractsWithRole;

class User extends Authenticatable
{
    use InteractsWithRole;
}

// Available methods
$user->hasAdminRole();
$user->hasUserRole();
$user->hasAccountantRole();
$user->hasAnyRole([UserRole::ADMIN, UserRole::ACCOUNTANT]);
```

### HasActiveStatus

```php
use App\Concerns\HasActiveStatus;

class User extends Authenticatable
{
    use HasActiveStatus;
}

// Available methods
User::onlyActive()->get();
User::onlyInactive()->get();
$user->isActive();
$user->activate();
$user->deactivate();
```

### HasSettings

```php
use App\Concerns\HasSettings;

class User extends Authenticatable
{
    use HasSettings;
}

// Available methods
$user->setSetting('theme', 'dark', 'string');
$user->getSetting('theme', 'light');
$user->hasSetting('theme');
$user->deleteSetting('theme');
```

## Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/users` | List users |
| GET | `/admin/users/create` | Create form |
| POST | `/admin/users` | Store user |
| GET | `/admin/users/{user}` | Show user |
| GET | `/admin/users/{user}/edit` | Edit form |
| PUT | `/admin/users/{user}` | Update user |
| DELETE | `/admin/users/{user}` | Delete user |
| POST | `/admin/users/{user}/toggle-status` | Toggle status |
| POST | `/admin/users/{user}/disconnect-telegram` | Unlink Telegram |

## Enums

### UserRole

```php
use App\Enums\UserRole;

UserRole::ADMIN->label();        // "Administrator"
UserRole::ADMIN->description();  // "Full system access"
UserRole::options();             // [['value' => 'admin', 'label' => 'Administrator'], ...]
```

### UserStatus

```php
use App\Enums\UserStatus;

UserStatus::ACTIVE->badge();     // "success"
UserStatus::INACTIVE->badge();   // "danger"
UserStatus::SUSPENDED->badge();  // "warning"
```

## Helper Functions

```php
// Check current user role
is_role('admin');    // true/false
is_admin();          // true/false
is_user();           // true/false
```

## Session Management

Users can view and manage their active sessions:

- View all active sessions
- Terminate specific sessions
- Terminate all other sessions

Access at `/sessions`

## Security

- Passwords hashed with bcrypt
- API tokens managed via Laravel Sanctum
- Sessions tracked in database
- Login attempts can be rate-limited

## Database Schema

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('status')->default('active');
    $table->string('role')->default('user');
    $table->string('locale')->default('en');
    $table->string('timezone')->nullable();
    $table->string('phone')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->string('telegram_chat_id')->nullable()->unique();
    $table->string('telegram_username')->nullable();
    $table->timestamp('telegram_verified_at')->nullable();
});
```
