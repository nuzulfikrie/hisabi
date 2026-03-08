# Settings System

Hisabi has a flexible settings system for both global (admin) and per-user configuration.

## Global Settings

Stored in `settings` table. Managed by administrators.

### Structure

```php
Setting::create([
    'key' => 'app.name',
    'name' => 'Application Name',
    'value' => 'Hisabi',
    'type' => 'string',      // string, boolean, json, number
    'group' => 'general',    // general, appearance, notification
    'description' => 'The name of the application',
]);
```

### Usage

```php
// Get setting
$value = Setting::get('app.name', 'Default');

// Set setting
Setting::set('app.name', 'My Hisabi');

// Helper function
setting('app.name', 'Default');
setting_set('app.name', 'My Hisabi');

// Get all settings in a group
$general = Setting::getGroup('general');
```

### Available Types

| Type | Storage | Retrieval |
|------|---------|-----------|
| `string` | As-is | String |
| `boolean` | "1" or "0" | Boolean |
| `json` | JSON encoded | Array |
| `number` | String | Float |

### Admin Interface

Access at `/admin/settings`

Settings are grouped by:
- **General**: App name, currency, locale
- **Appearance**: Theme, colors, logo
- **Notifications**: Email, webhook settings

## User Settings

Stored in `user_settings` table. Each user manages their own settings.

### Structure

```php
$user->setSetting('theme', 'dark', 'string');
$user->setSetting('notifications', true, 'boolean');
$user->setSetting('dashboard.widgets', ['chart', 'summary'], 'json');
```

### Usage

```php
// Set setting
$user->setSetting('key', $value, 'type');

// Get setting
$value = $user->getSetting('theme', 'light');

// Check existence
if ($user->hasSetting('theme')) {
    // ...
}

// Delete setting
$user->deleteSetting('theme');

// Get all settings
$settings = $user->settings;
```

### Settings Trait

```php
use App\Concerns\HasSettings;

class User extends Authenticatable
{
    use HasSettings;
}
```

## Common Settings

### Application

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `app.name` | string | `Hisabi` | Application name |
| `app.currency` | string | `MYR` | Default currency |
| `app.locale` | string | `en` | Default language |

### User Preferences

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `theme` | string | `light` | UI theme |
| `locale` | string | `en` | User language |
| `timezone` | string | `UTC` | User timezone |
| `notifications.email` | boolean | `true` | Email notifications |
| `notifications.telegram` | boolean | `true` | Telegram notifications |

### Telegram Settings

| Key | Type | Description |
|-----|------|-------------|
| `telegram_chat_id` | string | Linked Telegram chat ID |
| `telegram_username` | string | Telegram username |
| `telegram_verified_at` | datetime | When account was linked |

## Configuration File

`config/hisabi.php`:

```php
return [
    'currency' => env('HISABI_CURRENCY', 'MYR'),
    'locale' => env('HISABI_LOCALE', 'en'),
    'items_per_page' => 20,
];
```

## Seeding Default Settings

```php
// DatabaseSeeder.php
$settings = [
    [
        'key' => 'app.name',
        'name' => 'Application Name',
        'value' => 'Hisabi',
        'type' => 'string',
        'group' => 'general',
    ],
    [
        'key' => 'app.currency',
        'name' => 'Default Currency',
        'value' => 'MYR',
        'type' => 'string',
        'group' => 'general',
    ],
];

foreach ($settings as $setting) {
    Setting::firstOrCreate(['key' => $setting['key']], $setting);
}
```

## Best Practices

1. **Use descriptive keys**: `notification.email.enabled` not `notif_email`
2. **Group related settings**: Use `group` column for organization
3. **Type safety**: Always specify correct type for automatic casting
4. **Defaults**: Provide sensible defaults for all settings
5. **Validation**: Validate settings before saving

## API Endpoints

### User Settings

```
GET    /user/settings      → Get current user settings
POST   /user/settings      → Update settings
```

### Admin Settings

```
GET    /admin/settings     → List all settings
POST   /admin/settings     → Update settings
```
