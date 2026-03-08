# Hisabi Implementation Plan

Based on UMS project patterns, this document outlines the implementation plan for:
1. User Management
2. Session Management
3. Excel/CSV Export (Maatwebsite Excel)
4. Concerns (Reusable Traits)
5. Enums (Enhanced Pattern)
6. Support Files (Helper Functions)
7. Language Middleware
8. Settings (Admin & User)
9. **Telegram Bot Integration (NEW)** - Webhook + Laravel Actions

---

## Phase 1: Core Infrastructure

### 1.1 Install Required Packages

```bash
# Excel Export
composer require maatwebsite/excel

# Role-based permissions (optional, for user management)
composer require spatie/laravel-permission

# Laravel Actions (from UMS pattern - lorisleiva/laravel-actions)
composer require lorisleiva/laravel-actions

# Telegram Bot API
composer require irazasyed/telegram-bot-sdk

# Enum utilities (optional, from UMS pattern via cleaniquecoders/traitify)
# OR implement custom InteractsWithEnum trait
```

### 1.2 Create Support Directory Structure

```
support/
├── helpers.php          # Main loader (require_all_in pattern)
├── user.php            # User helper functions
├── setting.php         # Settings helper functions
├── export.php          # Export helper functions
├── locale.php          # Locale/language helper functions
└── telegram.php        # Telegram helper functions
```

Update `composer.json` autoload files:
```json
"autoload": {
    "files": [
        "support/helpers.php"
    ]
}
```

---

## Phase 2: Enums (Enhanced Pattern)

Based on UMS pattern using `InteractsWithEnum` trait with `label()`, `description()`, `badge()` methods.

### 2.1 Create InteractsWithEnum Contract & Trait

**File: `app/Contracts/Enum.php`**
```php
<?php

namespace App\Contracts;

interface Enum
{
    public function label(): string;
    public function description(): string;
}
```

**File: `app/Concerns/InteractsWithEnum.php`**
```php
<?php

namespace App\Concerns;

trait InteractsWithEnum
{
    public static function toArray(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
        ], self::cases());
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
```

### 2.2 Update Existing Enums

**Update: `app/Enums/Currency.php`**
- Implement `Contract\Enum`
- Use `InteractsWithEnum` trait
- Add `label()` and `description()` methods

**Update: `app/Enums/Locale.php`**
- Implement `Contract\Enum`
- Use `InteractsWithEnum` trait
- Add `badge()` method for UI colors

### 2.3 Create New Enums

**File: `app/Enums/UserStatus.php`** (from UMS pattern)
```php
enum UserStatus: string implements Contract
{
    use InteractsWithEnum;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function label(): string { ... }
    public function description(): string { ... }
    public function badge(): string { ... }  // 'success', 'danger', 'warning'
    public static function default(): self { ... }
}
```

**File: `app/Enums/UserRole.php`**
```php
enum UserRole: string implements Contract
{
    case ADMIN = 'admin';
    case USER = 'user';
    case ACCOUNTANT = 'accountant';
    
    public function label(): string { ... }
    public function description(): string { ... }
}
```

**File: `app/Enums/ExportFormat.php`**
```php
enum ExportFormat: string
{
    case EXCEL = 'xlsx';
    case CSV = 'csv';
    case PDF = 'pdf';
}
```

**File: `app/Enums/TelegramMessageStatus.php`** (NEW)
```php
enum TelegramMessageStatus: string
{
    case PENDING = 'pending';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case IGNORED = 'ignored';
    
    public function label(): string { ... }
    public function badge(): string { ... }
}
```

---

## Phase 3: Concerns (Reusable Traits)

Based on UMS `app/Concerns/` patterns.

### 3.1 Create Concern Files

**File: `app/Concerns/HasActiveStatus.php`** (from UMS)
```php
trait HasActiveStatus
{
    public function scopeOnlyActive($query) { ... }
    public function scopeOnlyInactive($query) { ... }
    public function isActive(): bool { ... }
}
```

**File: `app/Concerns/InteractsWithRole.php`** (from UMS)
```php
trait InteractsWithRole
{
    public function hasAdminRole(): bool { ... }
    public function hasUserRole(): bool { ... }
    protected function checkRole(UserRole $role): bool { ... }
}
```

**File: `app/Concerns/SearchQuery.php`** (from UMS)
```php
trait SearchQuery
{
    public function searchAny($model, $requestParam, $like) { ... }
    public function searchEqual($model, $requestParam, $like) { ... }
    public function searchBoolean($model, $requestParam, $like) { ... }
    public function searchWithin($model, $startedAt, $endedAt, $paramStartedAt, $paramEndedAt) { ... }
}
```

**File: `app/Concerns/HasSettings.php`** (new)
```php
trait HasSettings
{
    public function settings() { ... }
    public function getSetting(string $key, $default = null) { ... }
    public function setSetting(string $key, $value) { ... }
}
```

---

## Phase 4: User Management

### 4.1 Update User Model

**Update: `app/Models/User.php`**
```php
use App\Concerns\HasActiveStatus;
use App\Concerns\InteractsWithRole;
use App\Concerns\HasSettings;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class User extends Authenticatable
{
    use HasActiveStatus, InteractsWithRole, HasSettings;
    
    protected $fillable = [
        'name', 'email', 'password', 'status', 'role', 'locale', 'timezone',
        'telegram_chat_id', 'telegram_username', 'telegram_verified_at'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'telegram_verified_at' => 'datetime',
        'status' => UserStatus::class,
        'role' => UserRole::class,
    ];
    
    public function telegramTransactions()
    {
        return $this->hasMany(TelegramTransaction::class);
    }
}
```

### 4.2 Create User Management Migration

**Migration: Add fields to users table**
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('status')->default('active');
    $table->string('role')->default('user');
    $table->string('locale')->default('en');
    $table->string('timezone')->nullable();
    $table->string('phone')->nullable();
    $table->timestamp('last_login_at')->nullable();
    // Telegram fields
    $table->string('telegram_chat_id')->nullable()->unique();
    $table->string('telegram_username')->nullable();
    $table->timestamp('telegram_verified_at')->nullable();
});
```

### 4.3 Create User Management Controllers

**File: `app/Http/Controllers/Admin/UserController.php`** (from UMS pattern)
```php
class UserController extends Controller
{
    use SearchQuery;  // UMS pattern
    
    public function __construct()
    {
        // Permission middleware
        $this->middleware('permission:user-list', ['only' => ['index']]);
        $this->middleware('permission:user-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
    
    public function index(Request $request)
    {
        // Use SearchQuery trait methods
        $users = User::query();
        $users = $this->searchAny($users, $request->name, 'name');
        $users = $this->searchEqual($users, $request->status, 'status');
        $users = $users->paginate();
    }
}
```

### 4.4 User Management Routes

```php
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::post('users/{user}/disconnect-telegram', [UserController::class, 'disconnectTelegram']);
});
```

---

## Phase 5: Session Management

### 5.1 Create Query Session Service

**File: `app/Services/QuerySessionService.php`** (from UMS)
```php
class QuerySessionService
{
    protected string $sessionKey;

    public function __construct()
    {
        if (! is_null(request()->route())) {
            $this->sessionKey = 'query_string_'.request()->route()->getName();
        }
    }

    public function store(): void
    {
        session([$this->sessionKey => request()->query()]);
    }

    public function getBackUrl(string $routeName, array $extraParams = []): string
    {
        $key = 'query_string_'.$routeName;
        $query = session($key, []);
        $query = array_merge($query, $extraParams);

        return route($routeName).'?'.http_build_query($query);
    }
}
```

### 5.2 Create Session Controller

**File: `app/Http/Controllers/Admin/SessionController.php`**
```php
class SessionController extends Controller
{
    public function index()
    {
        // List active sessions for current user
        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->get();
        
        return view('admin.sessions.index', compact('sessions'));
    }
    
    public function destroy($sessionId)
    {
        // Invalidate specific session
        DB::table('sessions')->where('id', $sessionId)->delete();
        return back()->with('success', 'Session terminated');
    }
    
    public function destroyAll()
    {
        // Invalidate all other sessions
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();
        return back()->with('success', 'All other sessions terminated');
    }
}
```

---

## Phase 6: Excel/CSV Export

### 6.1 Create Export Classes

**File: `app/Exports/TransactionsExport.php`**
```php
namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Transaction::filter($this->filters)->get();
    }

    public function headings(): array
    {
        return ['ID', 'Date', 'Description', 'Amount', 'Type', 'Category', 'Brand'];
    }

    public function map($transaction): array
    {
        return [
            $transaction->id,
            $transaction->date->format('Y-m-d'),
            $transaction->description,
            $transaction->amount,
            $transaction->type,
            $transaction->category?->name,
            $transaction->brand?->name,
        ];
    }
}
```

**File: `app/Exports/ReportsExport.php`** (From View - UMS pattern)
```php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportsExport implements FromView
{
    protected $sections;
    protected $currency;
    protected $range;

    public function __construct($sections, $currency, $range)
    {
        $this->sections = $sections;
        $this->currency = $currency;
        $this->range = $range;
    }

    public function view(): View
    {
        return view('exports.report', [
            'sections' => $this->sections,
            'currency' => $this->currency,
            'range' => $this->range,
        ]);
    }
}
```

### 6.2 Create Abstract Export Service

**File: `app/Services/Exports/AbstractExportService.php`** (from UMS)
```php
abstract class AbstractExportService
{
    protected Builder $query;

    public function setQuery(Builder $query): static { ... }
    public function exportCsv(): StreamedResponse { ... }
    abstract protected function getExportHeaders(): array;
    protected function transformRow($record): array { ... }
    protected function resolveField(object $record, string $field): string { ... }
}
```

### 6.3 Create Export Controller

**File: `app/Http/Controllers/ExportController.php`**
```php
class ExportController extends Controller
{
    public function transactions(Request $request)
    {
        $format = $request->get('format', 'xlsx');
        $filename = 'transactions_'.now()->format('Ymd_His').'.'.$format;
        
        return Excel::download(
            new TransactionsExport($request->all()),
            $filename
        );
    }
    
    public function report(Request $request)
    {
        $sections = app(ReportManager::class)->generate(
            $request->start_date,
            $request->end_date
        );
        
        return Excel::download(
            new ReportsExport($sections, config('hisabi.currency'), $request->range),
            'report_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
```

### 6.4 Export Routes

```php
Route::middleware(['auth'])->prefix('exports')->group(function () {
    Route::get('/transactions', [ExportController::class, 'transactions'])->name('exports.transactions');
    Route::get('/report', [ExportController::class, 'report'])->name('exports.report');
});
```

---

## Phase 7: Language Middleware

### 7.1 Create Locale Middleware

**File: `app/Http/Middleware/LocaleMiddleware.php`** (from UMS)
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check user preference first, then session, then default
        $locale = auth()->user()?->locale 
            ?? Session::get('locale') 
            ?? config('app.locale');
            
        Session::put('locale', $locale);
        App::setLocale($locale);

        return $next($request);
    }
}
```

### 7.2 Register Middleware

**Update: `app/Http/Kernel.php`**
```php
protected $middleware = [
    // ...
    \App\Http\Middleware\LocaleMiddleware::class,
];

protected $routeMiddleware = [
    // ...
    'locale' => \App\Http\Middleware\LocaleMiddleware::class,
];
```

### 7.3 Create Locale Switcher

**File: `app/Http/Controllers/LocaleController.php`**
```php
class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (! in_array($locale, Locale::values())) {
            abort(400, 'Invalid locale');
        }
        
        Session::put('locale', $locale);
        
        // Update user preference if authenticated
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
        
        return redirect()->back();
    }
}
```

---

## Phase 8: Settings System

### 8.1 Create Settings Migration

**Migration: `create_settings_table.php`**
```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('name');
    $table->text('value')->nullable();
    $table->string('type')->default('string'); // string, boolean, json, number
    $table->string('group')->default('general'); // general, appearance, notification
    $table->text('description')->nullable();
    $table->timestamps();
});
```

### 8.2 Create Settings Model

**File: `app/Models/Setting.php`** (from UMS pattern)
```php
namespace App\Models;

class Setting extends Model
{
    protected $fillable = [
        'key', 'name', 'value', 'type', 'group', 'description'
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => in_array($setting->value, ['1', 'true', true], true),
            'json' => json_decode($setting->value, true),
            'number' => (float) $setting->value,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            $valueToStore = match ($setting->type ?? 'string') {
                'boolean' => $value ? '1' : '0',
                'json' => is_array($value) ? json_encode($value) : $value,
                default => (string) $value,
            };

            $setting->update(['value' => $valueToStore]);
        }
    }
}
```

### 8.3 Create User Settings Migration

**Migration: `create_user_settings_table.php`**
```php
Schema::create('user_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('key');
    $table->text('value')->nullable();
    $table->string('type')->default('string');
    $table->unique(['user_id', 'key']);
    $table->timestamps();
});
```

### 8.4 Create UserSettings Model

**File: `app/Models/UserSetting.php`**
```php
namespace App\Models;

class UserSetting extends Model
{
    protected $fillable = ['user_id', 'key', 'value', 'type'];
    
    protected $casts = [
        'value' => 'json',
    ];
}
```

### 8.5 Update HasSettings Concern

**Update: `app/Concerns/HasSettings.php`**
```php
trait HasSettings
{
    public function settings()
    {
        return $this->hasMany(UserSetting::class);
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public function setSetting(string $key, $value, string $type = 'string')
    {
        $valueToStore = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        return $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $valueToStore, 'type' => $type]
        );
    }
}
```

### 8.6 Create Settings Controllers

**File: `app/Http/Controllers/Admin/SettingController.php`** (Admin Settings)
```php
class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }
    
    public function update(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            Setting::set($key, $value);
        }
        
        return back()->with('success', 'Settings updated');
    }
}
```

**File: `app/Http/Controllers/UserSettingController.php`** (User Settings)
```php
class UserSettingController extends Controller
{
    public function index()
    {
        return view('user.settings.index');
    }
    
    public function update(Request $request)
    {
        $user = auth()->user();
        
        // Update profile settings
        if ($request->has('profile')) {
            $user->update($request->profile);
        }
        
        // Update preferences
        if ($request->has('preferences')) {
            foreach ($request->preferences as $key => $value) {
                $user->setSetting($key, $value);
            }
        }
        
        return back()->with('success', 'Settings updated');
    }
}
```

---

## Phase 9: Support Helper Functions

### 9.1 Create Helper Files

**File: `support/helpers.php`**
```php
<?php

if (! function_exists('require_all_in')) {
    function require_all_in(string $path)
    {
        collect(glob($path))
            ->each(function ($path) {
                if (basename($path) !== basename(__FILE__)) {
                    require $path;
                }
            });
    }
}

require_all_in(__DIR__.'/*.php');
```

**File: `support/setting.php`**
```php
<?php

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\Setting::get($key, $default);
    }
}

if (! function_exists('setting_set')) {
    function setting_set(string $key, mixed $value): void
    {
        \App\Models\Setting::set($key, $value);
    }
}
```

**File: `support/user.php`**
```php
<?php

if (! function_exists('is_role')) {
    function is_role(string $value): bool
    {
        $role = \App\Enums\UserRole::tryFrom($value);
        return $role && auth()->user()?->role === $role;
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return is_role('admin');
    }
}
```

**File: `support/export.php`**
```php
<?php

if (! function_exists('export_filename')) {
    function export_filename(string $prefix, string $ext = 'xlsx'): string
    {
        return $prefix.'_'.now()->format('Ymd_His').'.'.$ext;
    }
}
```

---

## Phase 10: Frontend Integration

### 10.1 Create Export Views

**File: `resources/views/exports/report.blade.php`**
```html
<table>
    <thead>
        <tr>
            <th colspan="4">Financial Report - {{ $range }}</th>
        </tr>
        <tr>
            <th>Section</th>
            <th>Item</th>
            <th>Value</th>
            <th>Currency</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sections as $section)
            @foreach($section['items'] as $item)
                <tr>
                    <td>{{ $section['title'] }}</td>
                    <td>{{ $item['label'] }}</td>
                    <td>{{ $item['value'] }}</td>
                    <td>{{ $currency }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
```

---

## Phase 11: Telegram Bot Integration (NEW)

### 11.1 Create Telegram Infrastructure

**Migration: `create_telegram_transactions_table.php`**
```php
Schema::create('telegram_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('telegram_chat_id');
    $table->string('telegram_message_id');
    $table->text('raw_message');
    $table->string('status')->default('pending'); // pending, processed, failed, ignored
    $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
    $table->text('parsed_data')->nullable(); // JSON of parsed transaction data
    $table->text('error_message')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();
    
    $table->index(['telegram_chat_id', 'status']);
    $table->unique(['telegram_chat_id', 'telegram_message_id']);
});
```

**Config: `config/telegram.php`**
```php
return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'bot_name' => env('TELEGRAM_BOT_NAME', 'HisabiBot'),
    'commands' => [
        'start' => \App\Telegram\Commands\StartCommand::class,
        'help' => \App\Telegram\Commands\HelpCommand::class,
        'link' => \App\Telegram\Commands\LinkCommand::class,
        'stats' => \App\Telegram\Commands\StatsCommand::class,
    ],
];
```

### 11.2 Create Telegram Models

**File: `app/Models/TelegramTransaction.php`**
```php
namespace App\Models;

use App\Enums\TelegramMessageStatus;
use Illuminate\Database\Eloquent\Model;

class TelegramTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'telegram_message_id',
        'raw_message',
        'status',
        'transaction_id',
        'parsed_data',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'parsed_data' => 'json',
        'processed_at' => 'datetime',
        'status' => TelegramMessageStatus::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', TelegramMessageStatus::PENDING);
    }

    public function markAsProcessed($transactionId): void
    {
        $this->update([
            'status' => TelegramMessageStatus::PROCESSED,
            'transaction_id' => $transactionId,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage): void
    {
        $this->update([
            'status' => TelegramMessageStatus::FAILED,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }
}
```

### 11.3 Create Laravel Actions for Telegram

Based on UMS pattern using `lorisleiva/laravel-actions` with `AsAction` trait.

**File: `app/Actions/Telegram/ParseTransactionMessage.php`**
```php
namespace App\Actions\Telegram;

use App\Contracts\Telegram\MessageParser;
use App\Models\TelegramTransaction;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Parse incoming Telegram message and extract transaction data.
 */
class ParseTransactionMessage
{
    use AsAction;

    public function __construct(
        private MessageParser $parser
    ) {}

    /**
     * Handle the action.
     *
     * @param string $message The raw message text
     * @param string $chatId Telegram chat ID
     * @param string $messageId Telegram message ID
     * @return array Parsed transaction data
     */
    public function handle(string $message, string $chatId, string $messageId): array
    {
        try {
            // Parse the message using the parser contract
            $parsedData = $this->parser->parse($message);
            
            Log::info('Telegram message parsed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'parsed_data' => $parsedData,
            ]);
            
            return $parsedData;
        } catch (\Exception $e) {
            Log::error('Failed to parse Telegram message', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Parse as a job (async processing).
     */
    public function asJob(string $message, string $chatId, string $messageId): void
    {
        $this->handle($message, $chatId, $messageId);
    }
}
```

**File: `app/Actions/Telegram/CreateTransactionFromMessage.php`**
```php
namespace App\Actions\Telegram;

use App\Models\TelegramTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\TelegramMessageStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Create a transaction from parsed Telegram message data.
 */
class CreateTransactionFromMessage
{
    use AsAction;

    /**
     * Handle the action.
     *
     * @param TelegramTransaction $telegramTransaction
     * @param array $parsedData
     * @return Transaction
     */
    public function handle(TelegramTransaction $telegramTransaction, array $parsedData): Transaction
    {
        return DB::transaction(function () use ($telegramTransaction, $parsedData) {
            // Find user by telegram chat ID
            $user = User::where('telegram_chat_id', $telegramTransaction->telegram_chat_id)->first();
            
            if (! $user) {
                throw new \Exception('User not linked to this Telegram account');
            }
            
            // Create the transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $parsedData['amount'],
                'description' => $parsedData['description'] ?? 'Telegram transaction',
                'type' => $parsedData['type'], // income or expense
                'category_id' => $parsedData['category_id'] ?? null,
                'brand_id' => $parsedData['brand_id'] ?? null,
                'date' => $parsedData['date'] ?? now(),
                'source' => 'telegram',
                'source_id' => $telegramTransaction->id,
            ]);
            
            // Update telegram transaction record
            $telegramTransaction->markAsProcessed($transaction->id);
            
            Log::info('Transaction created from Telegram', [
                'telegram_transaction_id' => $telegramTransaction->id,
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
            ]);
            
            return $transaction;
        });
    }

    /**
     * Handle failed transaction creation.
     */
    public function failed(TelegramTransaction $telegramTransaction, \Throwable $exception): void
    {
        $telegramTransaction->markAsFailed($exception->getMessage());
        
        Log::error('Failed to create transaction from Telegram', [
            'telegram_transaction_id' => $telegramTransaction->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**File: `app/Actions/Telegram/LinkTelegramAccount.php`**
```php
namespace App\Actions\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Link Telegram account to user.
 */
class LinkTelegramAccount
{
    use AsAction;

    public function handle(string $chatId, string $username, string $verificationCode): ?User
    {
        // Find user by verification code (stored in settings or temporary cache)
        $user = User::where('telegram_verification_code', $verificationCode)->first();
        
        if (! $user) {
            Log::warning('Invalid Telegram verification code', [
                'chat_id' => $chatId,
                'username' => $username,
            ]);
            return null;
        }
        
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_verified_at' => now(),
            'telegram_verification_code' => null, // Clear the code
        ]);
        
        Log::info('Telegram account linked', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
        ]);
        
        return $user;
    }
}
```

**File: `app/Actions/Telegram/SendTransactionConfirmation.php`**
```php
namespace App\Actions\Telegram;

use App\Models\Transaction;
use Telegram\Bot\Laravel\Facades\Telegram;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Send transaction confirmation message to Telegram.
 */
class SendTransactionConfirmation
{
    use AsAction;

    public function handle(Transaction $transaction): void
    {
        $user = $transaction->user;
        
        if (! $user->telegram_chat_id) {
            return;
        }
        
        $message = $this->formatMessage($transaction);
        
        Telegram::sendMessage([
            'chat_id' => $user->telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);
    }

    private function formatMessage(Transaction $transaction): string
    {
        $type = ucfirst($transaction->type);
        $amount = number_format($transaction->amount, 2);
        $currency = config('hisabi.currency');
        
        return <<<MESSAGE
<b>✅ Transaction Recorded</b>

<b>Type:</b> {$type}
<b>Amount:</b> {$currency} {$amount}
<b>Description:</b> {$transaction->description}
<b>Date:</b> {$transaction->date->format('Y-m-d')}

View in app: <a href="{route('transactions')}">Hisabi</a>
MESSAGE;
    }
}
```

### 11.4 Create Telegram Message Parser Contract

**File: `app/Contracts/Telegram/MessageParser.php`**
```php
namespace App\Contracts\Telegram;

interface MessageParser
{
    /**
     * Parse raw message text into structured transaction data.
     *
     * @param string $message Raw message text
     * @return array Parsed data with keys: amount, type, description, date, category_id, brand_id
     * @throws \InvalidArgumentException If message cannot be parsed
     */
    public function parse(string $message): array;
    
    /**
     * Check if this parser can handle the message.
     *
     * @param string $message
     * @return bool
     */
    public function canParse(string $message): bool;
}
```

**File: `app/Services/Telegram/SimpleMessageParser.php`**
```php
namespace App\Services\Telegram;

use App\Contracts\Telegram\MessageParser;
use App\Models\Category;
use Illuminate\Support\Str;

/**
 * Simple parser for Telegram transaction messages.
 * 
 * Expected formats:
 * - "expense 50 lunch at restaurant"
 * - "income 1000 salary"
 * - "-50 groceries" (defaults to expense)
 * - "+1000 freelance" (defaults to income)
 */
class SimpleMessageParser implements MessageParser
{
    public function parse(string $message): array
    {
        $message = trim($message);
        
        // Try to detect type and amount
        $type = $this->detectType($message);
        $amount = $this->extractAmount($message);
        $description = $this->extractDescription($message, $type, $amount);
        $categoryId = $this->detectCategory($description);
        
        if (! $amount || $amount <= 0) {
            throw new \InvalidArgumentException('Could not extract valid amount from message');
        }
        
        return [
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'date' => now(),
            'category_id' => $categoryId,
            'brand_id' => null,
        ];
    }

    public function canParse(string $message): bool
    {
        try {
            $this->parse($message);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function detectType(string $message): string
    {
        $message = strtolower($message);
        
        // Check for explicit type indicators
        if (str_starts_with($message, 'income') || str_starts_with($message, '+')) {
            return 'income';
        }
        
        if (str_starts_with($message, 'expense') || str_starts_with($message, '-')) {
            return 'expense';
        }
        
        // Default to expense
        return 'expense';
    }

    private function extractAmount(string $message): ?float
    {
        // Match numbers with optional decimal
        if (preg_match('/(?:^|[^\d])(\d+(?:\.\d{1,2})?)/', $message, $matches)) {
            return (float) $matches[1];
        }
        
        return null;
    }

    private function extractDescription(string $message, string $type, float $amount): string
    {
        // Remove type prefix
        $description = preg_replace('/^(income|expense)\s+/i', '', $message);
        
        // Remove amount
        $description = preg_replace('/\d+(?:\.\d{1,2})?/', '', $description, 1);
        
        // Remove +/- signs
        $description = ltrim($description, '+ -');
        
        return trim($description) ?: 'Telegram transaction';
    }

    private function detectCategory(string $description): ?int
    {
        $keywords = [
            'food' => ['lunch', 'dinner', 'food', 'restaurant', 'cafe'],
            'transport' => ['fuel', 'petrol', 'grab', 'taxi', 'bus'],
            'shopping' => ['groceries', 'mall', 'shopping'],
            'utilities' => ['electric', 'water', 'internet', 'bill'],
        ];
        
        $description = strtolower($description);
        
        foreach ($keywords as $categoryName => $words) {
            foreach ($words as $word) {
                if (Str::contains($description, $word)) {
                    $category = Category::where('name', 'like', "%{$categoryName}%")->first();
                    return $category?->id;
                }
            }
        }
        
        return null;
    }
}
```

### 11.5 Create Telegram Webhook Controller

**File: `app/Http/Controllers/Telegram/WebhookController.php`**
```php
namespace App\Http\Controllers\Telegram;

use App\Actions\Telegram\CreateTransactionFromMessage;
use App\Actions\Telegram\LinkTelegramAccount;
use App\Actions\Telegram\ParseTransactionMessage;
use App\Http\Controllers\Controller;
use App\Models\TelegramTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    /**
     * Handle incoming Telegram webhook.
     * POST /telegram/webhook
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $update = $request->all();
            
            Log::info('Telegram webhook received', ['update' => $update]);
            
            // Handle message
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
            
            // Handle callback queries (inline buttons)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
            
            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $messageId = $message['message_id'];
        $text = $message['text'] ?? '';
        $username = $message['from']['username'] ?? null;
        
        // Store raw message
        $telegramTransaction = TelegramTransaction::create([
            'telegram_chat_id' => $chatId,
            'telegram_message_id' => $messageId,
            'raw_message' => $text,
            'status' => 'pending',
        ]);
        
        // Handle bot commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $chatId, $username);
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }
        
        // Find linked user
        $user = \App\Models\User::where('telegram_chat_id', $chatId)->first();
        
        if (! $user) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Please link your account first. Use /link command to get started.",
            ]);
            $telegramTransaction->update(['status' => 'ignored']);
            return;
        }
        
        $telegramTransaction->update(['user_id' => $user->id]);
        
        try {
            // Parse message using Laravel Action
            $parsedData = ParseTransactionMessage::run($text, $chatId, $messageId);
            
            // Update with parsed data
            $telegramTransaction->update(['parsed_data' => $parsedData]);
            
            // Create transaction using Laravel Action
            $transaction = CreateTransactionFromMessage::run($telegramTransaction, $parsedData);
            
            // Send confirmation
            \App\Actions\Telegram\SendTransactionConfirmation::run($transaction);
            
        } catch (\Exception $e) {
            $telegramTransaction->markAsFailed($e->getMessage());
            
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "❌ Could not process your message. Please use format:\n\nexpense 50 lunch at restaurant\nor\nincome 1000 salary",
            ]);
        }
    }

    private function handleCommand(string $text, string $chatId, ?string $username): void
    {
        $command = explode(' ', $text)[0];
        
        match ($command) {
            '/start' => $this->handleStartCommand($chatId),
            '/help' => $this->handleHelpCommand($chatId),
            '/link' => $this->handleLinkCommand($chatId, $username),
            '/stats' => $this->handleStatsCommand($chatId),
            default => $this->handleUnknownCommand($chatId),
        };
    }

    private function handleStartCommand(string $chatId): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "Welcome to Hisabi Bot! 🤖\n\nI can help you track expenses and income.\n\nTo get started:\n1. Link your account with /link\n2. Send messages like:\n   • expense 50 lunch\n   • income 1000 salary\n   • -25 coffee\n   • +500 freelance",
        ]);
    }

    private function handleHelpCommand(string $chatId): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "<b>Hisabi Bot Commands</b>\n\n/link - Link your account\n/stats - View your stats\n/help - Show this help\n\n<b>Message Formats:</b>\n• expense [amount] [description]\n• income [amount] [description]\n• -[amount] [description] (expense)\n• +[amount] [description] (income)",
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleLinkCommand(string $chatId, ?string $username): void
    {
        // Generate verification code
        $code = strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Store in cache temporarily (or use temporary token)
        cache()->put("telegram_link:{$code}", [
            'chat_id' => $chatId,
            'username' => $username,
        ], now()->addMinutes(30));
        
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "To link your account:\n\n1. Login to Hisabi web app\n2. Go to Settings → Telegram\n3. Enter this code: <b>{$code}</b>\n\nCode expires in 30 minutes.",
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleStatsCommand(string $chatId): void
    {
        $user = \App\Models\User::where('telegram_chat_id', $chatId)->first();
        
        if (! $user) {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => "Please link your account first with /link",
            ]);
            return;
        }
        
        $stats = \App\Domains\Transaction\Services\TransactionService::getUserStats($user->id);
        
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "<b>Your Stats</b>\n\nTotal Income: {$stats['income']}\nTotal Expense: {$stats['expense']}\nBalance: {$stats['balance']}",
            'parse_mode' => 'HTML',
        ]);
    }

    private function handleUnknownCommand(string $chatId): void
    {
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text' => "Unknown command. Use /help for available commands.",
        ]);
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        // Handle inline keyboard button clicks
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        
        // Acknowledge the callback
        Telegram::answerCallbackQuery([
            'callback_query_id' => $callbackQuery['id'],
        ]);
    }
}
```

### 11.6 Create Telegram Setup Command

**File: `app/Console/Commands/Telegram/SetWebhookCommand.php`**
```php
namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook:set 
                            {--remove : Remove webhook instead of setting}';
    
    protected $description = 'Set or remove Telegram bot webhook';

    public function handle(): int
    {
        if ($this->option('remove')) {
            Telegram::removeWebhook();
            $this->info('Webhook removed successfully');
            return 0;
        }
        
        $url = config('telegram.webhook_url');
        
        if (! $url) {
            $this->error('TELEGRAM_WEBHOOK_URL not configured');
            return 1;
        }
        
        Telegram::setWebhook(['url' => $url]);
        
        $this->info("Webhook set to: {$url}");
        
        // Get webhook info
        $info = Telegram::getWebhookInfo();
        $this->table(
            ['Property', 'Value'],
            [
                ['URL', $info->url],
                ['Has Custom Cert', $info->has_custom_certificate ? 'Yes' : 'No'],
                ['Pending Updates', $info->pending_update_count],
                ['IP Address', $info->ip_address ?? 'N/A'],
            ]
        );
        
        return 0;
    }
}
```

### 11.7 Register Telegram Routes

**Update: `routes/web.php`**
```php
// Telegram webhook (public, uses token verification)
Route::post('/telegram/webhook', [\App\Http\Controllers\Telegram\WebhookController::class, 'handle'])
    ->name('telegram.webhook');

// Telegram settings (auth required)
Route::middleware(['auth'])->group(function () {
    Route::get('/settings/telegram', [\App\Http\Controllers\Telegram\SettingsController::class, 'index'])
        ->name('settings.telegram');
    Route::post('/settings/telegram/link', [\App\Http\Controllers\Telegram\SettingsController::class, 'link'])
        ->name('settings.telegram.link');
    Route::post('/settings/telegram/unlink', [\App\Http\Controllers\Telegram\SettingsController::class, 'unlink'])
        ->name('settings.telegram.unlink');
});
```

### 11.8 Telegram Service Provider

**File: `app/Providers/TelegramServiceProvider.php`**
```php
namespace App\Providers;

use App\Contracts\Telegram\MessageParser;
use App\Services\Telegram\SimpleMessageParser;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageParser::class, SimpleMessageParser::class);
    }

    public function boot(): void
    {
        // Register Telegram bot commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\Telegram\SetWebhookCommand::class,
            ]);
        }
    }
}
```

### 11.9 Register in Config App

**Update: `config/app.php`**
```php
'providers' => [
    // ...
    \App\Providers\TelegramServiceProvider::class,
],
```

### 11.10 Support Helpers for Telegram

**Update: `support/telegram.php`**
```php
<?php

if (! function_exists('telegram_bot')) {
    /**
     * Get Telegram Bot instance.
     */
    function telegram_bot(): \Telegram\Bot\Api
    {
        return \Telegram\Bot\Laravel\Facades\Telegram::getFacadeRoot();
    }
}

if (! function_exists('telegram_send_message')) {
    /**
     * Send message via Telegram.
     */
    function telegram_send_message(string $chatId, string $message, array $options = []): \Telegram\Bot\Objects\Message
    {
        return \Telegram\Bot\Laravel\Facades\Telegram::sendMessage(array_merge([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ], $options));
    }
}

if (! function_exists('telegram_parse_transaction')) {
    /**
     * Parse transaction message using the parser.
     */
    function telegram_parse_transaction(string $message): array
    {
        return app(\App\Contracts\Telegram\MessageParser::class)->parse($message);
    }
}
```

---

## Implementation Checklist

### Phase 1: Core Infrastructure
- [ ] Install maatwebsite/excel
- [ ] Install spatie/laravel-permission (optional)
- [ ] Install lorisleiva/laravel-actions
- [ ] Install irazasyed/telegram-bot-sdk
- [ ] Create support/ directory structure
- [ ] Update composer.json autoload

### Phase 2: Enums
- [ ] Create Enum Contract
- [ ] Create InteractsWithEnum trait
- [ ] Update Currency enum
- [ ] Update Locale enum
- [ ] Create UserStatus enum
- [ ] Create UserRole enum
- [ ] Create ExportFormat enum
- [ ] Create TelegramMessageStatus enum

### Phase 3: Concerns
- [ ] Create HasActiveStatus trait
- [ ] Create InteractsWithRole trait
- [ ] Create SearchQuery trait
- [ ] Create HasSettings trait

### Phase 4: User Management
- [ ] Update User model with traits
- [ ] Create user management migration (including telegram fields)
- [ ] Create Admin\UserController
- [ ] Create user management routes

### Phase 5: Session Management
- [ ] Create QuerySessionService
- [ ] Create SessionController
- [ ] Create session management routes

### Phase 6: Excel/CSV Export
- [ ] Create TransactionsExport class
- [ ] Create ReportsExport class
- [ ] Create AbstractExportService
- [ ] Create ExportController
- [ ] Create export routes

### Phase 7: Language Middleware
- [ ] Create LocaleMiddleware
- [ ] Register middleware in Kernel
- [ ] Create LocaleController
- [ ] Create language switcher routes

### Phase 8: Settings System
- [ ] Create settings migration
- [ ] Create Setting model
- [ ] Create user_settings migration
- [ ] Create UserSetting model
- [ ] Create Admin\SettingController
- [ ] Create UserSettingController
- [ ] Create settings routes

### Phase 9: Support Helpers
- [ ] Create helpers.php loader
- [ ] Create setting.php helpers
- [ ] Create user.php helpers
- [ ] Create export.php helpers
- [ ] Create telegram.php helpers

### Phase 10: Frontend
- [ ] Create export views
- [ ] Create settings UI
- [ ] Create user management UI
- [ ] Add language switcher to layout

### Phase 11: Telegram Integration (NEW)
- [ ] Create telegram_transactions migration
- [ ] Create TelegramTransaction model
- [ ] Create config/telegram.php
- [ ] Create MessageParser contract
- [ ] Create SimpleMessageParser
- [ ] Create ParseTransactionMessage Action
- [ ] Create CreateTransactionFromMessage Action
- [ ] Create LinkTelegramAccount Action
- [ ] Create SendTransactionConfirmation Action
- [ ] Create WebhookController
- [ ] Create SetWebhookCommand
- [ ] Create TelegramSettingsController
- [ ] Create TelegramServiceProvider
- [ ] Add Telegram routes
- [ ] Register TelegramServiceProvider
- [ ] Create telegram support helpers
- [ ] Add .env variables (TELEGRAM_BOT_TOKEN, TELEGRAM_WEBHOOK_URL)
- [ ] Test webhook locally (using ngrok or similar)
- [ ] Deploy and set webhook

---

## Notes

1. **UMS Pattern References:**
   - Enums use `InteractsWithEnum` trait + `Contract\Enum` interface
   - Concerns are stored in `app/Concerns/`
   - Support helpers are stored in `support/` and auto-loaded via composer
   - Settings use key-value pattern with type casting
   - Export uses Maatwebsite Excel with FromView/FromCollection
   - **Actions use `lorisleiva/laravel-actions` with `AsAction` trait** (from UMS)

2. **Hisabi Adaptations:**
   - Using Inertia.js for frontend (existing pattern)
   - API-first approach for data (existing pattern)
   - Simpler role system (admin/user vs complex UMS roles)
   - Focus on financial data exports
   - **Telegram integration for quick expense/income tracking**

3. **Laravel Actions Pattern (from UMS):**
   - Simple invokable classes with `handle()` method
   - Use `AsAction` trait for additional features (running as jobs, etc.)
   - Can be called statically with `::run()` or as invokable `()`
   - Support for async processing with `asJob()` method
   - Good for encapsulating business logic (parsing, creating, sending)

4. **Telegram Bot Features:**
   - Webhook-based message processing
   - Account linking with verification code
   - Natural language transaction parsing
   - Confirmation messages
   - Stats command
   - Extensible with more commands

5. **Optional Enhancements:**
   - Add spatie/laravel-activitylog for user activity tracking
   - Add spatie/laravel-permission for granular permissions
   - Add cleaniquecoders/traitify for enhanced enum utilities
   - Add AI-powered transaction categorization for Telegram messages
