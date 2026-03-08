# Session Management

Hisabi includes session tracking and management for security and user control.

## Features

- Track active sessions across devices
- View session details (IP, browser, last activity)
- Terminate specific sessions
- Terminate all other sessions (logout everywhere)

## How It Works

Sessions are stored in the database (`sessions` table) with:
- Session ID
- User ID
- IP address
- User agent (browser/device)
- Last activity timestamp
- Payload (encrypted session data)

## User Interface

Access session management at `/sessions`

### Features

- **View Sessions**: List all active sessions
- **Current Session**: Highlighted for easy identification
- **Device Info**: Shows browser and approximate location
- **Terminate**: Logout specific devices
- **Logout All**: Secure account by ending all other sessions

## Query Session Service

For preserving filters and search state across pages:

```php
use App\Services\QuerySessionService;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Store current query in session
        app(QuerySessionService::class)->store();
        
        // Later, get back URL with preserved query
        $backUrl = app(QuerySessionService::class)
            ->getBackUrl('users.index');
            
        // Returns: /users?search=john&sort=name
    }
}
```

### Use Cases

- Preserving search filters after edit
- Remembering pagination state
- Maintaining sort order

## Security

### Automatic Cleanup

Inactive sessions are automatically purged by Laravel:

```php
// config/session.php
'lifetime' => 120,  // 2 hours
'expire_on_close' => false,
```

### Session Validation

Sessions validate:
- IP address (optional)
- User agent (optional)

```php
// config/session.php
'same_site' => 'lax',
'secure' => env('SESSION_SECURE_COOKIE', true),
```

## Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/sessions` | List active sessions |
| DELETE | `/sessions/{sessionId}` | Terminate specific session |
| DELETE | `/sessions` | Terminate all other sessions |

## Controller

```php
class SessionController extends Controller
{
    public function index()
    {
        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderBy('last_activity', 'desc')
            ->get();
            
        return view('sessions.index', [
            'sessions' => $sessions,
            'currentSessionId' => session()->getId(),
        ]);
    }
    
    public function destroy($sessionId)
    {
        // Prevent killing current session
        if ($sessionId === session()->getId()) {
            return back()->with('error', 'Cannot terminate current session');
        }
        
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->delete();
            
        return back()->with('success', 'Session terminated');
    }
    
    public function destroyAll()
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();
            
        return back()->with('success', 'All other sessions terminated');
    }
}
```

## Session Configuration

`config/session.php`:

```php
return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION', null),
    'table' => 'sessions',
    'store' => env('SESSION_STORE', null),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'hisabi_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
```

## Using Redis for Sessions

For better performance:

```env
SESSION_DRIVER=redis
SESSION_CONNECTION=default
```

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
],
```

## Best Practices

1. **Use HTTPS** in production (secure cookies)
2. **Short lifetime** for sensitive apps (30-60 min)
3. **Regenerate ID** after login
4. **Invalidate on logout**
5. **Monitor active sessions** for suspicious activity
