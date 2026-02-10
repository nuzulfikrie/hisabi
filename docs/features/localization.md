# Localization (i18n)

Hisabi supports multiple languages with a flexible localization system.

## Supported Languages

| Code | Language | Status |
|------|----------|--------|
| `en` | English | ✅ Complete |
| `ms` | Malay (Bahasa Malaysia) | ✅ Complete |

## User Language Selection

Users can change their language via:
- Web interface (settings)
- Telegram bot (if implemented)
- Browser preference (auto-detected)

## How It Works

### Priority Order

1. **User preference** (stored in database)
2. **Session value** (previously selected)
3. **Browser preference** (Accept-Language header)
4. **Default** (config `app.locale`)

### Locale Middleware

The `LocaleMiddleware` handles language switching automatically:

```php
// app/Http/Middleware/LocaleMiddleware.php
public function handle($request, $next)
{
    $locale = auth()->user()?->locale 
        ?? Session::get('locale') 
        ?? $request->getPreferredLanguage(['en', 'ms'])
        ?? config('app.locale');
        
    App::setLocale($locale);
    Session::put('locale', $locale);
    
    return $next($request);
}
```

## Switching Language

### Web Interface

```php
// LocaleController
public function switch(Request $request, string $locale)
{
    if (! in_array($locale, Locale::values())) {
        abort(400);
    }
    
    // Update user preference
    if (auth()->check()) {
        auth()->user()->update(['locale' => $locale]);
    }
    
    // Update session
    session(['locale' => $locale]);
    
    return redirect()->back();
}
```

### Route

```
GET /locale/{locale}  → Switch language
```

## Translation Files

Located in `lang/` directory:

```
lang/
├── en/
│   ├── auth.php
│   ├── pagination.php
│   ├── passwords.php
│   └── validation.php
└── ms/
    ├── auth.php
    ├── pagination.php
    ├── passwords.php
    └── validation.php
```

### Creating Translations

```php
// lang/en/messages.php
return [
    'welcome' => 'Welcome to Hisabi',
    'expense_added' => 'Expense added successfully',
    'income_added' => 'Income added successfully',
];

// lang/ms/messages.php
return [
    'welcome' => 'Selamat datang ke Hisabi',
    'expense_added' => 'Perbelanjaan berjaya ditambah',
    'income_added' => 'Pendapatan berjaya ditambah',
];
```

### Using Translations

```php
// PHP
__('messages.welcome');
__('messages.expense_added');

// With parameters
__('messages.hello', ['name' => 'John']); // Hello, John

// Blade
{{ __('messages.welcome') }}
@lang('messages.welcome')
```

## Enums with Localization

```php
// App\Enums\UserStatus
enum UserStatus: string
{
    case ACTIVE = 'active';
    
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('Active'), // Uses translation
        };
    }
}
```

## Helper Functions

```php
// Get current locale
current_locale();  // 'en'

// Set locale
set_locale('ms');

// Check if RTL
is_rtl();  // false (for en, ms)
```

## Adding a New Language

1. **Create translation files**:
   ```bash
   mkdir -p lang/fr
   cp lang/en/*.php lang/fr/
   ```

2. **Update Locale enum**:
   ```php
   enum Locale: string
   {
       case ENGLISH = 'en';
       case MALAY = 'ms';
       case FRENCH = 'fr';  // Add
   }
   ```

3. **Translate files**:
   ```php
   // lang/fr/messages.php
   return [
       'welcome' => 'Bienvenue sur Hisabi',
   ];
   ```

4. **Update config**:
   ```php
   // config/app.php
   'available_locales' => ['en', 'ms', 'fr'],
   ```

## RTL Support

For RTL languages (Arabic, Hebrew):

```php
// Locale enum
public function isRtl(): bool
{
    return match ($this) {
        self::ARABIC => true,
        default => false,
    };
}
```

In Blade:
```blade
<html dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
```

## Date & Number Localization

```php
// Localized date
carbon()->locale('ms')->isoFormat('dddd, D MMMM YYYY');
// "Isnin, 10 Februari 2025"

// Localized number
number_format(1234.56, 2);  // Depends on locale
```

## Best Practices

1. **Always use keys**, not hardcoded strings
2. **Keep translations organized** by feature/page
3. **Use parameters** instead of concatenation
4. **Test in all languages** before deploying
5. **Cache translations** in production

## See Also

- [Laravel Localization](https://laravel.com/docs/localization)
- [Carbon Localization](https://carbon.nesbot.com/docs/#api-localization)
