# UnoPim - MIDDLEWARE Layer Patterns & Skills

> Reference documentation for the Middleware architectural layer.
> Generated from exhaustive codebase scan - 2026-02-08

---

## Executive Overview

UnoPim implements a comprehensive middleware layer across three distinct request paths:
1. **Web Middleware Stack** - Session-based admin panel authentication
2. **API Middleware Stack** - OAuth2 Passport-based token authentication
3. **Custom Middleware Layer** - Channel/locale context, authorization (ACL), and security headers

The architecture supports **dual-guard authentication** (session for web, OAuth2/Passport for API) with unified permission/ACL model for both channels.

---

## 1. HTTP KERNEL & MIDDLEWARE GROUPS

**File**: `app/Http/Kernel.php`

### Global HTTP Middleware (Every Request)

These execute for **all incoming requests** in order:

```php
protected $middleware = [
    \App\Http\Middleware\TrimStrings::class,
    \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \Webkul\Core\Http\Middleware\SecureHeaders::class,
    \Webkul\Core\Http\Middleware\CheckForMaintenanceMode::class,
    \Webkul\Installer\Http\Middleware\CanInstall::class,
];
```

| Middleware | Purpose | Order |
|------------|---------|-------|
| `TrimStrings` | Removes leading/trailing whitespace from input | 1st |
| `TrustProxies` | Detects real client IP behind load balancers | 2nd |
| `HandleCors` | Enables Cross-Origin Resource Sharing (CORS) | 3rd |
| `ValidatePostSize` | Validates POST request payload size | 4th |
| `SecureHeaders` | Injects security headers (XSS, HSTS, etc) | 5th |
| `CheckForMaintenanceMode` | Blocks traffic during maintenance (custom) | 6th |
| `CanInstall` | Enforces installation workflow (custom) | 7th |

### Middleware Groups

#### 'web' Middleware Group

**For web routes** - Session-based with CSRF protection:

```php
'web' => [
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

**Execution order and purpose**:
1. **EncryptCookies** - Encrypts sensitive cookies
2. **AddQueuedCookiesToResponse** - Queues cookies to response
3. **StartSession** - Initializes session (uses `file` driver by default)
4. **ShareErrorsFromSession** - Makes validation errors available to views
5. **VerifyCsrfToken** - Validates CSRF tokens on state-changing requests
6. **SubstituteBindings** - Resolves route model bindings

#### 'api' Middleware Group

**For API routes** - Rate limiting + route binding:

```php
'api' => [
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

1. **ThrottleRequests:api** - Rate limits to 60 requests/minute per user/IP
2. **SubstituteBindings** - Resolves implicit route bindings

### Middleware Aliases

```php
protected $middlewareAliases = [
    'auth'          => \Illuminate\Auth\Middleware\Authenticate::class,
    'auth.basic'    => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'auth.session'  => \Illuminate\Session\Middleware\AuthenticateSession::class,
    'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can'           => \Illuminate\Auth\Middleware\Authorize::class,
    'guest'         => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'signed'        => \Illuminate\Routing\Middleware\ValidateSignature::class,
    'throttle'      => \Illuminate\Routing\Middleware\ThrottleRequests::class,
];
```

---

## 2. CUSTOM MIDDLEWARE - SECURITY LAYER

### 2.1 SecureHeaders Middleware

**Namespace**: `Webkul\Core\Http\Middleware\SecureHeaders`
**File**: `packages/Webkul/Core/src/Http/Middleware/SecureHeaders.php`
**Applied to**: All routes (global middleware)

**Purpose**: Removes fingerprinting headers and injects security headers

```php
public function handle($request, Closure $next)
{
    $this->removeUnwantedHeaders();
    $response = $next($request);
    $this->setHeaders($response);
    return $response;
}
```

**Removes Headers**:
- `X-Powered-By` - Framework fingerprinting
- `Server` - Server software version

**Sets Headers**:

| Header | Value | Purpose |
|--------|-------|---------|
| `Referrer-Policy` | `no-referrer-when-downgrade` | Controls referrer info in cross-origin requests |
| `X-Content-Type-Options` | `nosniff` | Prevents MIME type sniffing |
| `X-XSS-Protection` | `1; mode=block` | Legacy XSS protection (deprecated) |
| `X-Frame-Options` | `SAMEORIGIN` | Prevents clickjacking - only same-origin frames |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Forces HTTPS for 1 year |

### 2.2 CheckForMaintenanceMode Middleware

**Namespace**: `Webkul\Core\Http\Middleware\CheckForMaintenanceMode`
**Extends**: `Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode`
**Applied to**: All routes (global middleware)

**Purpose**: Custom maintenance mode with admin access bypass and IP whitelist

```php
public function handle($request, Closure $next)
{
    if ($this->databaseManager->isInstalled() && $this->app->isDownForMaintenance()) {
        $response = $next($request);

        if (in_array($request->ip(), $this->excludedIPs) || $this->shouldPassThrough($request)) {
            return $response;
        }

        throw new HttpException(503);
    }

    return $next($request);
}
```

**Logic**:
1. Checks if app is installed AND in maintenance mode
2. Allows requests matching:
   - Whitelisted IPs (from database)
   - Admin URL (e.g., `/admin/*`)
3. Otherwise throws 503 Service Unavailable

**Configuration**:
- Automatically excludes: `config('app.admin_url')` (typically `/admin`)
- Loads allowed IPs: `$channel->allowed_ips` (comma-separated)

### 2.3 CanInstall Middleware

**Namespace**: `Webkul\Installer\Http\Middleware\CanInstall`
**Applied to**: All routes (global middleware)

**Purpose**: Enforces installation workflow - prevents access to app until installed

```php
public function handle(Request $request, Closure $next)
{
    if (Str::contains($request->getPathInfo(), '/install')) {
        if ($this->isAlreadyInstalled() && !$request->ajax()) {
            if (file_exists(realpath(__DIR__.'/../../../../../../public/install.php'))) {
                unlink(realpath(__DIR__.'/../../../../../../public/install.php'));
            }
            return redirect()->route('admin.dashboard.index');
        }
    } else {
        if (!$this->isAlreadyInstalled()) {
            return redirect()->route('installer.index');
        }
    }

    return $next($request);
}
```

**Flow**:
1. If URL contains `/install`:
   - If already installed & not AJAX: redirect to dashboard + delete install.php
2. If NOT `/install` URL:
   - If NOT installed: redirect to installer.index
3. Otherwise allow request

**Installation Detection**:
- Checks `storage_path('installed')` file existence
- OR checks database via `DatabaseManager::isInstalled()`
- Creates `storage/installed` marker on first successful installation

---

## 3. AUTHENTICATION MIDDLEWARE LAYER

### Guard Configuration

**File**: `config/auth.php`

```php
'defaults' => [
    'guard'     => 'admin',        // Default guard for web
    'passwords' => 'admins',
],

'guards' => [
    'admin' => [
        'driver'   => 'session',   // Session-based for web panel
        'provider' => 'admins',
    ],

    'api' => [
        'driver'   => 'passport',  // OAuth2 for API
        'provider' => 'admins',
    ],
],

'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model'  => Webkul\User\Models\Admin::class,
    ],
],
```

### Dual-Guard Pattern

**Web Admin Panel** uses the `admin` guard (session-based).
**API Clients** use the `api` guard (OAuth2 Passport).

Both authenticate against the same `Admin` model with a shared permission system.

---

## 4. AUTHORIZATION & BOUNCER MIDDLEWARE

### 4.1 Bouncer Middleware (Session-based Admin)

**Namespace**: `Webkul\User\Http\Middleware\Bouncer`
**File**: `packages/Webkul/User/src/Http/Middleware/Bouncer.php`
**Applied to**: Admin routes with `['middleware' => ['admin']]`
**Guard**: `admin` (session)

**Purpose**: Enforces authentication, status validation, and locale preference

```php
public function handle($request, Closure $next, $guard = 'admin')
{
    // 1. Check authentication
    if (!auth()->guard($guard)->check()) {
        return redirect()->route('admin.session.create');
    }

    // 2. Check user status (soft-delete mechanism)
    if (!(bool) auth()->guard($guard)->user()->status) {
        auth()->guard($guard)->logout();
        return redirect()->route('admin.session.create');
    }

    // 3. Validate user has permissions
    if ($this->isPermissionsEmpty()) {
        auth()->guard('admin')->logout();
        session()->flash('error', __('admin::app.error.403.message'));
        return redirect()->route('admin.session.create');
    }

    // 4. Set user's preferred UI locale
    $userLocaleCode = auth($guard)?->user()?->uiLocale()?->first()?->code;
    if ($userLocaleCode && app()->getLocale() !== $userLocaleCode) {
        app()->setLocale($userLocaleCode);
    } else {
        app()->setLocale(config('app.locale'));
    }

    return $next($request);
}
```

**Security Checks** (in order):

| Check | Failure Action | Purpose |
|-------|----------------|---------|
| User authenticated? | Redirect to login | Requires session |
| User status = true? | Logout + redirect | Admins can disable users |
| Has any permissions? | Logout + redirect | Prevents orphaned users |
| Check ACL for route | Via `bouncer()->allow()` | Enforces permission-based access |

**Permission Validation**:

```php
public function isPermissionsEmpty()
{
    if (!$role = auth()->guard('admin')->user()->role) {
        abort(401, 'This action is unauthorized.');
    }

    // Admins with 'all' permissions always allowed
    if ($role->permission_type === 'all') {
        return false;  // Not empty
    }

    // Custom permission users with empty array = denied
    if ($role->permission_type !== 'all' && empty($role->permissions)) {
        return true;  // Empty - logout
    }

    // Check if authorized for current route via ACL
    $this->checkIfAuthorized();

    return false;  // Not empty
}

public function checkIfAuthorized()
{
    $acl = app('acl');

    if (!$acl) {
        return;
    }

    if (isset($acl->roles[Route::currentRouteName()])) {
        bouncer()->allow($acl->roles[Route::currentRouteName()]);
    }
}
```

### 4.2 Bouncer Class (Authorization Helper)

**Namespace**: `Webkul\User\Bouncer`
**File**: `packages/Webkul/User/src/Bouncer.php`

**Helper function** (in `packages/Webkul/User/src/Http/helpers.php`):

```php
if (!function_exists('bouncer')) {
    function bouncer()
    {
        return app()->make(\Webkul\User\Bouncer::class);
    }
}
```

**Bouncer Methods**:

```php
public function hasPermission($permission)
{
    // Admins with 'all' permission type always authorized
    if (auth()->guard('admin')->check() &&
        auth()->guard('admin')->user()->role->permission_type == 'all') {
        return true;
    }

    // Check if user has specific permission
    if (!auth()->guard('admin')->check() ||
        !auth()->guard('admin')->user()->hasPermission($permission)) {
        return false;
    }

    return true;
}

public static function allow($permission)
{
    // Throws 401 if user lacks permission
    if (!auth()->guard('admin')->check() ||
        !auth()->guard('admin')->user()->hasPermission($permission)) {
        abort(401, 'This action is unauthorized');
    }
}
```

**Usage in Views**:
```blade
@if (bouncer()->hasPermission('admin.settings.roles.index'))
    <!-- Show menu item -->
@endif
```

---

## 5. API AUTHENTICATION MIDDLEWARE

### 5.1 OAuth2 Passport Configuration

**File**: `packages/Webkul/AdminApi/src/Providers/AdminApiServiceProvider.php`

```php
protected function activatePassportApiClient(): void
{
    Passport::loadKeysFrom(__DIR__.'/../Secrets/Oauth');

    Passport::$passwordGrantEnabled = true;
    Passport::useClientModel(\Webkul\AdminApi\Models\Client::class);

    // Access token TTL: Default 3600s (1 hour)
    Passport::tokensExpireIn(Carbon::now()->addSeconds(config('api.access_token_ttl')));

    // Refresh token TTL: Default 3600s (1 hour)
    Passport::refreshTokensExpireIn(Carbon::now()->addSeconds(config('api.refresh_token_ttl')));
}
```

**Configuration** (from `config/api.php`):
```php
'access_token_ttl'  => env('ACCESS_TOKEN_TTL', 3600),   // 1 hour
'refresh_token_ttl' => env('REFRESH_TOKEN_TTL', 3600),  // 1 hour
```

### 5.2 Custom API Middleware Stack

**Registered in AdminApiServiceProvider**:

```php
protected $middlewareAliases = [
    'accept.json'    => \Webkul\AdminApi\Http\Middleware\EnsureAcceptsJson::class,
    'request.locale' => \Webkul\AdminApi\Http\Middleware\LocaleMiddleware::class,
    'api.scope'      => \Webkul\AdminApi\Http\Middleware\ScopeMiddleware::class,
];

protected function activateMiddlewareAliases()
{
    collect($this->middlewareAliases)->each(function ($className, $alias) {
        $this->app['router']->aliasMiddleware($alias, $className);
    });
}
```

#### 5.2.1 EnsureAcceptsJson Middleware

**Namespace**: `Webkul\AdminApi\Http\Middleware\EnsureAcceptsJson`
**Applied to**: API routes group
**Guard**: None (validation-only)

```php
public function handle(Request $request, Closure $next)
{
    if ($request->header('Accept') !== 'application/json') {
        return response()->json(['error' => 'Accept header must be application/json'], 406);
    }

    return $next($request);
}
```

**Purpose**: Enforces JSON request/response contract
**Validation**: `Accept: application/json` header required
**Failure Response**: HTTP 406 Not Acceptable

#### 5.2.2 LocaleMiddleware

**Namespace**: `Webkul\AdminApi\Http\Middleware\LocaleMiddleware`
**Applied to**: API routes group
**Dependencies**: `LocaleRepository`

```php
public function handle(Request $request, Closure $next)
{
    $requestedLocales = $this->getLocales($request);

    if ($requestedLocales) {
        $activeLocales = $this->localeRepository->getActiveLocales()->pluck('code')->toArray();
        $localeNotExists = array_diff($requestedLocales, $activeLocales);

        if (count($localeNotExists) > 0) {
            $validator = Validator::make([], []);
            $validator->after(function ($validator) use ($localeNotExists) {
                $validator->errors()->add('locale',
                    trans('admin::app.validations.invalid-locale',
                        ['locales' => json_encode($localeNotExists)])
                );
            });

            if ($validator->fails()) {
                return $this->validateErrorResponse($validator);
            }
        }
    }

    return $next($request);
}

public function getLocales()
{
    $requestData = request()->all();

    // Check for 'labels' key first
    $locales = $this->checkKeyExists($requestData, 'labels');

    // Fall back to 'locale_specific' key
    if (!$locales) {
        $locales = $this->checkKeyExists($requestData, 'locale_specific');
    }

    return $locales;
}
```

**Purpose**: Validates locale codes in API request payloads
**Supported Keys**: `labels` or `locale_specific`
**Validation**: All locales must exist in active locales list
**Failure Response**: 422 Validation error with invalid locale list

#### 5.2.3 ScopeMiddleware (API Authorization)

**Namespace**: `Webkul\AdminApi\Http\Middleware\ScopeMiddleware`
**Applied to**: API routes group
**Guard**: `api` (Passport)

```php
public function handle(Request $request, Closure $next)
{
    if ($this->getAclForCurrentRoute() && !$this->hasPermission($this->getAclForCurrentRoute())) {
        return response()->json(['error' => 'This action is unauthorized'], 403);
    }

    return $next($request);
}

public function hasPermission($permission)
{
    // Full permission API key users bypass ACL
    if (auth()->guard('api')->check() &&
        auth()->guard('api')->user()->apiKey->permission_type == 'all') {
        return true;
    }

    // Check specific permission on API key
    if (!auth()->guard('api')->check() ||
        !auth()->guard('api')->user()->apiKey->hasPermission($permission)) {
        return false;
    }

    return true;
}

public function getAclForCurrentRoute()
{
    $acl = app('api-acl');

    if (!$acl) {
        return;
    }

    // Map .get to .index for ACL lookup
    return $acl->roles[str_replace('.get', '.index', Route::currentRouteName())] ?? null;
}
```

**Purpose**: Authorizes API requests based on API key permissions
**Two Permission Types**:
1. `permission_type = 'all'` - Full access to all API endpoints
2. `permission_type = 'custom'` - Limited to specific permissions array

**ACL Source**: `config('api-acl')` singleton service
**Failure Response**: HTTP 403 Forbidden

---

## 6. LOCALE & CHANNEL MIDDLEWARE

### 6.1 EnsureChannelLocaleIsValid Middleware

**Namespace**: `Webkul\Admin\Http\Middleware\EnsureChannelLocaleIsValid`
**File**: `packages/Webkul/Admin/src/Http/Middleware/EnsureChannelLocaleIsValid.php`
**Applied to**: Product edit routes specifically

```php
public function handle(Request $request, Closure $next)
{
    $requestedChannel = core()->getRequestedChannel();
    $requestedLocaleCode = core()->getRequestedLocaleCode();
    $route = $request->route();

    // Check if locale is available for requested channel
    if ($requestedChannel?->locales()?->where('code', $requestedLocaleCode)->first() === null) {
        $parameters = $route->parameters();

        $requestedChannel ??= core()->getDefaultChannel();

        // Redirect to first available locale for that channel
        $parameters['channel'] = $requestedChannel->code;
        $parameters['locale'] = $requestedChannel->locales()->first()->code;

        $routeName = $route->getName();

        if ($routeName !== null) {
            return redirect()->route($routeName, $parameters);
        }

        $actionName = $route->getActionName();

        if ($actionName !== null) {
            return redirect()->action($actionName, $parameters);
        }

        return redirect()->back();
    }

    return $next($request);
}
```

**Purpose**: Ensures locale is available for selected channel
**Use Case**: Multi-channel, multi-locale product editing
**Example**:
- If user requests channel=de (German), locale=fr (French)
- But German channel only supports en, de
- Middleware redirects to channel=de, locale=en

---

## 7. INSTALLER MIDDLEWARE

### Locale Middleware (Installer)

**Namespace**: `Webkul\Installer\Http\Middleware\Locale`
**File**: `packages/Webkul/Installer/src/Http/Middleware/Locale.php`
**Applied to**: Installer routes

```php
public function handle($request, Closure $next)
{
    if ($localeCode = $request->query('locale')) {
        app()->setLocale($localeCode);
        session()->put('installer_locale', $localeCode);
    } else {
        app()->setLocale(session()->get('installer_locale') ?? config('app.locale'));
    }

    return $next($request);
}
```

**Purpose**: Handles locale switching during installation process
**Source**: URL query parameter `?locale=es`
**Persistence**: Stores in session for next requests

---

## 8. APPLICATION MIDDLEWARE (Laravel Defaults)

### 8.1 TrimStrings Middleware

**File**: `app/Http/Middleware/TrimStrings.php`

```php
protected $except = [
    'current_password',
    'password',
    'password_confirmation',
];
```

**Purpose**: Removes whitespace from all inputs except sensitive fields
**Exemptions**: Password fields must preserve input (users may use spaces)

### 8.2 TrustProxies Middleware

**File**: `app/Http/Middleware/TrustProxies.php`

```php
protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB;
```

**Purpose**: Trusts proxy headers for real IP/protocol detection
**Supported Headers**:
- `X-Forwarded-For` - Real client IP
- `X-Forwarded-Host` - Original Host
- `X-Forwarded-Port` - Original port
- `X-Forwarded-Proto` - Original protocol (HTTP/HTTPS)
- `X-Forwarded-AWS-ELB` - AWS load balancer

### 8.3 VerifyCsrfToken Middleware

**File**: `app/Http/Middleware/VerifyCsrfToken.php`

```php
protected $except = [
    //
];
```

**Purpose**: Validates CSRF token on POST/PUT/DELETE
**Exemptions**: None configured (all routes require token)

### 8.4 RedirectIfAuthenticated Middleware

**File**: `app/Http/Middleware/RedirectIfAuthenticated.php`

```php
public function handle(Request $request, Closure $next, string ...$guards): Response
{
    $guards = empty($guards) ? [null] : $guards;

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            return redirect(RouteServiceProvider::HOME);
        }
    }

    return $next($request);
}
```

**Purpose**: Redirects authenticated users from auth pages (login, register)
**Redirect**: To `HOME` (configured as `/admin`)

### 8.5 EncryptCookies Middleware

**File**: `app/Http/Middleware/EncryptCookies.php`
**Applied to**: Web routes group

**Purpose**: Encrypts sensitive cookies for security
**Decrypts** automatically on request

---

## 9. SESSION CONFIGURATION

**File**: `config/session.php`

```php
'driver'             => env('SESSION_DRIVER', 'file'),
'lifetime'           => env('SESSION_LIFETIME', 30),           // 30 minutes
'expire_on_close'    => true,                                  // Expire on browser close
'encrypt'            => false,                                 // Not encrypted at rest
'files'              => storage_path('framework/sessions'),    // File storage location
'cookie'             => 'unopim_session',                      // Cookie name
'http_only'          => true,                                  // JS cannot access
'secure'             => env('SESSION_SECURE_COOKIE', null),    // HTTPS-only if set
```

**Session Lifecycle**:
- User logs in -> Session created -> Stored in `/storage/framework/sessions`
- Session expires on browser close OR after 30 minutes idle
- Session destroyed on logout

---

## 10. CORS CONFIGURATION

**File**: `config/cors.php`

```php
'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods'          => ['*'],                              // All HTTP methods
'allowed_origins'          => ['*'],                              // All origins
'allowed_origins_patterns' => [],
'allowed_headers'          => ['*'],                              // All headers
'exposed_headers'          => [],
'max_age'                  => 0,                                  // No preflight caching
'supports_credentials'     => false,                              // No credentials in CORS
```

**Middleware**: `Illuminate\Http\Middleware\HandleCors::class` (global)
**Applied to**: `/api/*` routes
**Security Note**: Very permissive - consider restricting in production

---

## 11. RATE LIMITING

**File**: `app/Providers/RouteServiceProvider.php`

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

**Configuration**:
- **Limit**: 60 requests per minute
- **Key**: User ID if authenticated, otherwise IP address
- **Applied to**: Routes using `'throttle:api'` middleware

---

## 12. ROUTE MIDDLEWARE APPLICATION

### Admin Web Routes

**File**: `packages/Webkul/Admin/src/Routes/web.php`

```php
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    // Catalog, Settings, Config routes...
});
```

**Middleware Stack for Web Routes**:
```
Global Middleware
  |
  v
Web Group Middleware (session, CSRF, etc)
  |
  v
'admin' alias -> Bouncer::class
  |
  v
Route Handler
```

**Example Route with Custom Middleware**:
```php
Route::get('edit/{id}', 'edit')
    ->name('admin.catalog.products.edit')
    ->middleware(EnsureChannelLocaleIsValid::class);
```

### API Routes

**File**: `packages/Webkul/AdminApi/src/Routes/admin-api.php`

```php
Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'auth:api',        // Verify Passport token
        'api.scope',       // Check API key permissions (custom)
        'accept.json',     // Validate Accept header (custom)
        'request.locale',  // Validate locales (custom)
    ],
], function () {
    // API endpoints...
});
```

**Middleware Stack for API Routes**:
```
Global Middleware
  |
  v
API Group Middleware (throttle, bindings)
  |
  v
auth:api -> Passport token validation
  |
  v
api.scope -> ScopeMiddleware (ACL check)
  |
  v
accept.json -> EnsureAcceptsJson
  |
  v
request.locale -> LocaleMiddleware
  |
  v
Route Handler
```

---

## 13. PERMISSION & ACL SYSTEM

### Admin Model Relationships

**File**: `packages/Webkul/User/src/Models/Admin.php`

```php
public function role(): BelongsTo
{
    return $this->belongsTo(RoleProxy::modelClass());
}

public function apiKey(): BelongsTo
{
    return $this->belongsTo(Apikey::class);
}
```

### Role Model

**File**: `packages/Webkul/User/src/Models/Role.php`

```php
protected $fillable = [
    'name',
    'description',
    'permission_type',  // 'all' or 'custom'
    'permissions',      // JSON array of permission keys
];

protected $casts = [
    'permissions' => 'array',
];
```

### API Key Model (Apikey)

**File**: `packages/Webkul/AdminApi/src/Models/Apikey.php`

```php
protected $fillable = [
    'name',
    'admin_id',
    'oauth_client_id',
    'permission_type',  // 'all' or 'custom'
    'permissions',      // JSON array of permission keys
    'revoked',          // Boolean - if true, revokes access
];

protected $casts = [
    'permissions' => 'array',
];

public function hasPermission($permission)
{
    // Implementation validates permission against array
}
```

### Permission Types

| Type | Behavior | Use Case |
|------|----------|----------|
| `'all'` | Full access to all actions | Super admin roles |
| `'custom'` | Limited to specific permissions | Regular admin roles or API key restrictions |

### ACL Configuration

**Web ACL**: `config('acl')` - Registered in AdminServiceProvider
**API ACL**: `config('api-acl')` - Registered in AdminApiServiceProvider

**ACL Entry Structure**:
```php
[
    'key'   => 'admin.settings.roles.index',
    'name'  => 'admin::app.acl.roles',
    'route' => 'admin.settings.roles.index',
    'sort'  => 1,
]
```

**Flow**:
1. Route matching ACL key is requested
2. Bouncer middleware calls `checkIfAuthorized()`
3. Looks up route name in `$acl->roles` array
4. Calls `bouncer()->allow($permission)` if match found
5. Throws 401 if permission not held

---

## 14. MIDDLEWARE EXECUTION FLOW DIAGRAMS

### Web Admin Request Flow

```
Request
  |
  v
Global Middleware (in order)
  1. TrimStrings
  2. TrustProxies
  3. HandleCors
  4. ValidatePostSize
  5. SecureHeaders
  6. CheckForMaintenanceMode
  7. CanInstall
  |
  v
Web Group Middleware
  1. EncryptCookies
  2. AddQueuedCookiesToResponse
  3. StartSession
  4. ShareErrorsFromSession
  5. VerifyCsrfToken
  6. SubstituteBindings
  |
  v
Route Middleware ('admin' alias)
  -> Bouncer::class
    - Check authentication
    - Check user status
    - Check permissions
    - Check ACL
    - Set locale preference
  |
  v
Route-Specific Middleware (if any)
  e.g., EnsureChannelLocaleIsValid
  |
  v
Controller Action
  |
  v
Response -> SecureHeaders (sets headers) -> Back to Client
```

### API Request Flow

```
Request (with Authorization: Bearer <token>)
  |
  v
Global Middleware (same as web)
  |
  v
API Group Middleware
  1. ThrottleRequests:api (60/min)
  2. SubstituteBindings
  |
  v
API Route Middleware Stack
  1. auth:api
     - Validates Passport token
     - Loads authenticated Admin user + apiKey relationship
  2. api.scope (ScopeMiddleware)
     - Checks apiKey.permission_type
     - Validates permission against route ACL
  3. accept.json
     - Validates Accept: application/json header
  4. request.locale
     - Validates locale codes in payload
  |
  v
Controller Action
  |
  v
Response (JSON) -> Back to Client
```

---

## 15. AUTHENTICATION FLOW SUMMARIES

### Session-Based (Admin Web)

```
1. User submits login form
   -> SessionController::store()
   -> auth('admin')->attempt($credentials)

2. Session created in storage_path('framework/sessions')

3. Subsequent requests:
   -> StartSession middleware reads session cookie
   -> Session state loaded into auth()->guard('admin')
   -> Bouncer middleware validates status + permissions

4. Logout:
   -> auth('admin')->logout()
   -> Session destroyed
```

### OAuth2-Based (API)

```
1. API client requests token (password grant flow)
   POST /oauth/token
   {
       "grant_type": "password",
       "client_id": "...",
       "client_secret": "...",
       "username": "admin@example.com",
       "password": "..."
   }

2. Passport generates access token (expires in 1 hour)
   + refresh token (expires in 1 hour)

3. Subsequent API requests include token
   Authorization: Bearer <access_token>

4. auth:api middleware:
   -> Validates token signature
   -> Checks expiration
   -> Loads Admin + ApiKey models

5. ScopeMiddleware checks ApiKey.permissions

6. Token refresh:
   POST /oauth/token with grant_type=refresh_token
```

---

## 16. CONFIGURATION MATRIX

| Component | Default | Environment Variable | Purpose |
|-----------|---------|---------------------|---------|
| Session lifetime | 30 min | SESSION_LIFETIME | How long before auto-logout |
| Session driver | file | SESSION_DRIVER | Where to store sessions |
| HTTPS only | not set | SESSION_SECURE_COOKIE | Force HTTPS cookies |
| Access token TTL | 3600s | ACCESS_TOKEN_TTL | API token expiration |
| Refresh token TTL | 3600s | REFRESH_TOKEN_TTL | How long to refresh access |
| API rate limit | 60/min | - | Requests per user/minute |
| Admin URL | /admin | APP_ADMIN_URL | Admin panel base path |
| Default locale | en | APP_LOCALE | Fallback language |

---

## 17. KEY SECURITY FEATURES

1. **Dual Authentication**
   - Session for web (CSRF-protected)
   - OAuth2 for API (token-based)

2. **Permission Hierarchy**
   - Role-level permissions (admin users)
   - API key-level permissions (API clients)
   - Route-level ACL enforcement

3. **Security Headers**
   - HSTS (HTTPS enforcement)
   - XSS protection
   - Clickjacking prevention (X-Frame-Options)
   - MIME type sniffing prevention

4. **CSRF Protection**
   - Token validation on state-changing requests
   - HttpOnly cookies (prevent JS access)

5. **Maintenance Mode**
   - Admin bypass
   - IP whitelist support
   - Installation protection

6. **User Status Control**
   - Admins can disable/enable users
   - Automatic logout if disabled mid-session
   - Status check on every request

---

## 18. MIDDLEWARE FILE INVENTORY

| Middleware Class | Package | File Path |
|-----------------|---------|-----------|
| `App\Http\Middleware\TrimStrings` | App | `app/Http/Middleware/TrimStrings.php` |
| `App\Http\Middleware\TrustProxies` | App | `app/Http/Middleware/TrustProxies.php` |
| `App\Http\Middleware\EncryptCookies` | App | `app/Http/Middleware/EncryptCookies.php` |
| `App\Http\Middleware\VerifyCsrfToken` | App | `app/Http/Middleware/VerifyCsrfToken.php` |
| `App\Http\Middleware\RedirectIfAuthenticated` | App | `app/Http/Middleware/RedirectIfAuthenticated.php` |
| `Webkul\Core\Http\Middleware\SecureHeaders` | Core | `packages/Webkul/Core/src/Http/Middleware/SecureHeaders.php` |
| `Webkul\Core\Http\Middleware\CheckForMaintenanceMode` | Core | `packages/Webkul/Core/src/Http/Middleware/CheckForMaintenanceMode.php` |
| `Webkul\Installer\Http\Middleware\CanInstall` | Installer | `packages/Webkul/Installer/src/Http/Middleware/CanInstall.php` |
| `Webkul\Installer\Http\Middleware\Locale` | Installer | `packages/Webkul/Installer/src/Http/Middleware/Locale.php` |
| `Webkul\User\Http\Middleware\Bouncer` | User | `packages/Webkul/User/src/Http/Middleware/Bouncer.php` |
| `Webkul\Admin\Http\Middleware\EnsureChannelLocaleIsValid` | Admin | `packages/Webkul/Admin/src/Http/Middleware/EnsureChannelLocaleIsValid.php` |
| `Webkul\AdminApi\Http\Middleware\EnsureAcceptsJson` | AdminApi | `packages/Webkul/AdminApi/src/Http/Middleware/EnsureAcceptsJson.php` |
| `Webkul\AdminApi\Http\Middleware\LocaleMiddleware` | AdminApi | `packages/Webkul/AdminApi/src/Http/Middleware/LocaleMiddleware.php` |
| `Webkul\AdminApi\Http\Middleware\ScopeMiddleware` | AdminApi | `packages/Webkul/AdminApi/src/Http/Middleware/ScopeMiddleware.php` |
