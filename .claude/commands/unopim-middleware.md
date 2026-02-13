# UnoPim MIDDLEWARE Layer Skill

Use this skill when working with HTTP middleware, authentication, security headers, locale/channel validation, CORS, rate limiting, or session configuration.

## HTTP Kernel Stack

**File:** `app/Http/Kernel.php`

### Global Middleware (every request, in order)
1. `TrimStrings` - Whitespace removal
2. `TrustProxies` - Real IP behind load balancers
3. `HandleCors` - CORS headers
4. `ValidatePostSize` - POST size validation
5. `SecureHeaders` - Security headers (custom)
6. `CheckForMaintenanceMode` - Maintenance bypass (custom)
7. `CanInstall` - Installation enforcement (custom)

### Web Group
`EncryptCookies` → `AddQueuedCookies` → `StartSession` → `ShareErrorsFromSession` → `VerifyCsrfToken` → `SubstituteBindings`

### API Group
`ThrottleRequests:api` (60/min) → `SubstituteBindings`

### Middleware Aliases
```php
'auth' => Authenticate, 'guest' => RedirectIfAuthenticated,
'can' => Authorize, 'throttle' => ThrottleRequests,
'signed' => ValidateSignature, 'cache.headers' => SetCacheHeaders
```

## Authentication (Dual-Guard)

**Config:** `config/auth.php`
```php
'guards' => [
    'admin' => ['driver' => 'session', 'provider' => 'admins'],  // Web panel
    'api'   => ['driver' => 'passport', 'provider' => 'admins'], // API
],
'providers' => [
    'admins' => ['driver' => 'eloquent', 'model' => Webkul\User\Models\Admin::class],
]
```

### Web Auth: Bouncer Middleware
**File:** `packages/Webkul/User/src/Http/Middleware/Bouncer.php`
**Applied via:** `['middleware' => ['admin']]` on routes

Flow:
1. `auth()->guard('admin')->check()` → redirect to login if false
2. Check `user->status` → logout if disabled
3. Check `isPermissionsEmpty()` → logout if no permissions
4. Check ACL for current route via `bouncer()->allow($permission)`
5. Set UI locale from user preference

```php
// Permission checking in code
bouncer()->hasPermission('catalog.products.edit'); // returns bool
bouncer()->allow('catalog.products.edit');          // aborts 401 if denied

// In Blade templates
@if (bouncer()->hasPermission('admin.settings.roles.index'))
    <!-- Visible only with permission -->
@endif
```

### API Auth: ScopeMiddleware
**File:** `packages/Webkul/AdminApi/src/Http/Middleware/ScopeMiddleware.php`
**Applied via:** `'api.scope'` middleware alias

Flow:
1. Get ACL key for current route from `config('api-acl')`
2. Check `apiKey->permission_type` - if `'all'`, allow everything
3. If `'custom'`, check specific permission in `apiKey->permissions` array
4. Return 403 if denied

### Passport Config
```php
Passport::tokensExpireIn(Carbon::now()->addSeconds(env('ACCESS_TOKEN_TTL', 3600)));
Passport::refreshTokensExpireIn(Carbon::now()->addSeconds(env('REFRESH_TOKEN_TTL', 3600)));
Passport::$passwordGrantEnabled = true;
```

## Security Headers (SecureHeaders)

**File:** `packages/Webkul/Core/src/Http/Middleware/SecureHeaders.php`

**Removes:** `X-Powered-By`, `Server`
**Sets:**
| Header | Value |
|--------|-------|
| Referrer-Policy | no-referrer-when-downgrade |
| X-Content-Type-Options | nosniff |
| X-XSS-Protection | 1; mode=block |
| X-Frame-Options | SAMEORIGIN |
| Strict-Transport-Security | max-age=31536000; includeSubDomains |

## API Middleware

### EnsureAcceptsJson
Requires `Accept: application/json` header, returns 406 if missing.

### LocaleMiddleware
Validates locale codes in request data (checks `labels` or `locale_specific` keys against active locales). Returns 422 with invalid locales list.

### EnsureChannelLocaleIsValid
Applied to product edit routes. Redirects if locale is not available for the requested channel.

## CheckForMaintenanceMode
- Bypasses: admin URL paths, whitelisted IPs from `channel->allowed_ips`
- Uses `$this->app->isDownForMaintenance()` check

## CanInstall
- Checks `storage_path('installed')` file or `DatabaseManager::isInstalled()`
- Redirects to installer if not installed
- Redirects to dashboard if already installed and on `/install` path

## Configuration Reference

| Setting | Value |
|---------|-------|
| Rate limit | 60 requests/min per user/IP |
| Session lifetime | 120 min (from `SESSION_LIFETIME` env) |
| Session cookie | `unopim_session` |
| Session driver | `file` (default) |
| CSRF | Enabled for all web POST/PUT/DELETE |
| Token TTL | 3600s access, 3600s refresh (env configurable) |

## Adding New Middleware

```php
// 1. Create class
namespace Webkul\MyPackage\Http\Middleware;

class MyMiddleware
{
    public function handle($request, Closure $next)
    {
        // Pre-processing
        $response = $next($request);
        // Post-processing
        return $response;
    }
}

// 2a. Register globally in app/Http/Kernel.php $middleware array
// 2b. Or register as alias in $middlewareAliases
// 2c. Or register in ServiceProvider:
$this->app['router']->aliasMiddleware('my.middleware', MyMiddleware::class);
```

## Key Rules

- NEVER bypass Bouncer/ScopeMiddleware - all admin routes MUST use `['middleware' => ['admin']]`
- API routes MUST include all 4 middleware: `auth:api`, `api.scope`, `accept.json`, `request.locale`
- SecureHeaders is global - do NOT remove security headers in custom middleware
- `permission_type = 'all'` bypasses ALL ACL checks (both web and API)
- Bouncer checks ACL via route name mapping in `app('acl')` singleton
- ScopeMiddleware checks ACL via route name mapping in `app('api-acl')` singleton
- Installation state is determined by `storage/installed` file marker
- Session uses `expire_on_close: true` by default
