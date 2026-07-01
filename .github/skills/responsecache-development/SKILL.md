---
name: responsecache-development
description: Cache entire HTTP responses using spatie/laravel-responsecache, including standard caching, flexible (stale-while-revalidate) caching, cache profiles, replacers, and selective cache clearing.
---

# Response Cache Development

## When to use this skill

Use this skill when working with HTTP response caching in a Laravel application using `spatie/laravel-responsecache`. This includes adding cache middleware to routes, configuring cache profiles, clearing cached responses, creating custom replacers, or working with flexible (stale-while-revalidate) caching.

## Applying cache middleware to routes

Use the `CacheResponse` middleware to cache entire responses:

```php
use Spatie\ResponseCache\Middlewares\CacheResponse;

// Cache with default lifetime (from config)
Route::middleware(CacheResponse::class)->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});

// Cache with custom lifetime using CarbonInterval
Route::middleware(CacheResponse::for(lifetime: CarbonInterval::minutes(10)))->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});

// Cache with tags for selective clearing
Route::middleware(CacheResponse::for(lifetime: CarbonInterval::hour(), tags: 'posts'))->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

## Flexible (stale-while-revalidate) caching

Serve stale content while refreshing in the background using `FlexibleCacheResponse`:

```php
use Spatie\ResponseCache\Middlewares\FlexibleCacheResponse;

Route::middleware(FlexibleCacheResponse::for(
    lifetime: CarbonInterval::minutes(5),
    grace: CarbonInterval::minute(),
    tags: 'posts',
))->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

The response is considered fresh for `lifetime` seconds. After that, stale content is served while a background refresh happens within the `grace` period.

## PHP attributes on controller methods

Use attributes instead of middleware for per-action control:

```php
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Attributes\NoCache;

class PostController
{
    // Cache with custom lifetime (seconds) and tags
    #[Cache(lifetime: 600, tags: ['posts'])]
    public function index() { /* ... */ }

    // Flexible cache with lifetime and grace (seconds)
    #[FlexibleCache(lifetime: 300, grace: 60, tags: ['posts'])]
    public function popular() { /* ... */ }

    // Prevent caching on a specific action
    #[NoCache]
    public function create() { /* ... */ }
}
```

Attributes work when the `CacheResponse` or `FlexibleCacheResponse` middleware is applied to the route.

## Preventing caching

Use the `DoNotCacheResponse` middleware to prevent caching:

```php
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;

Route::middleware(DoNotCacheResponse::class)->group(function () {
    Route::get('/account', [AccountController::class, 'show']);
});
```

Or use the `#[NoCache]` attribute on individual controller methods.

## Clearing cached responses

```php
use Spatie\ResponseCache\Facades\ResponseCache;

// Clear all cached responses
ResponseCache::clear();

// Clear only responses with specific tags (requires a tag-supporting cache driver)
ResponseCache::clear(['posts']);

// Forget specific URLs
ResponseCache::forget('/posts');
ResponseCache::forget(['/posts', '/posts/popular']);

// Forget specific URLs with tags
ResponseCache::forget('/posts', ['posts']);

// Selective clearing with the fluent API
ResponseCache::selectCachedItems()
    ->forUrls('/posts', '/posts/popular')
    ->usingTags('posts')
    ->forget();
```

Clear via Artisan:

```bash
php artisan responsecache:clear
```

## Creating a custom cache profile

Implement the `CacheProfile` interface to control what gets cached:

```php
use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Symfony\Component\HttpFoundation\Response;

class CustomCacheProfile implements CacheProfile
{
    public function enabled(Request $request): bool
    {
        // Return false to disable caching entirely
        return true;
    }

    public function shouldCacheRequest(Request $request): bool
    {
        return $request->isMethod('GET');
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful();
    }

    public function cacheLifetimeInSeconds(Request $request): int
    {
        return 3600;
    }

    public function useCacheNameSuffix(Request $request): string
    {
        // Return a unique suffix to separate caches, e.g. per user
        return auth()->check() ? (string) auth()->id() : '';
    }
}
```

Register in `config/responsecache.php`:

```php
'cache_profile' => App\CacheProfiles\CustomCacheProfile::class,
```

You can also extend `Spatie\ResponseCache\CacheProfiles\BaseCacheProfile` for sensible defaults.

## Creating a custom replacer

Replacers swap dynamic content (like CSRF tokens) so cached responses stay valid:

```php
use Spatie\ResponseCache\Replacers\Replacer;
use Symfony\Component\HttpFoundation\Response;

class UserNameReplacer implements Replacer
{
    public function prepareResponseToCache(Response $response): void
    {
        // Replace the actual value with a placeholder before caching
        $content = $response->getContent();
        $response->setContent(str_replace(
            auth()->user()->name,
            '<USERNAME_PLACEHOLDER>',
            $content,
        ));
    }

    public function replaceInCachedResponse(Response $response): void
    {
        // Replace the placeholder with the current value when serving
        $content = $response->getContent();
        $response->setContent(str_replace(
            '<USERNAME_PLACEHOLDER>',
            auth()->user()?->name ?? '',
            $content,
        ));
    }
}
```

Register in `config/responsecache.php`:

```php
'replacers' => [
    \Spatie\ResponseCache\Replacers\CsrfTokenReplacer::class,
    \App\Replacers\UserNameReplacer::class,
],
```

## Creating a custom hasher

Implement `RequestHasher` to customize how cache keys are generated:

```php
use Illuminate\Http\Request;
use Spatie\ResponseCache\Hasher\RequestHasher;

class CustomHasher implements RequestHasher
{
    public function getHashFor(Request $request): string
    {
        return hash('xxh128', $request->getUri());
    }
}
```

Register in `config/responsecache.php`:

```php
'hasher' => App\Hashers\CustomHasher::class,
```

## Events

The package dispatches these events:

- `ResponseCacheHitEvent` — a cached response was served. Properties: `$request`, `$ageInSeconds`, `$tags`.
- `CacheMissedEvent` — no cached response found. Properties: `$request`.
- `ClearingResponseCacheEvent` — cache is about to be cleared.
- `ClearedResponseCacheEvent` — cache was cleared successfully.
- `ClearingResponseCacheFailedEvent` — cache clearing failed.

## Configuration

Key configuration options in `config/responsecache.php`:

- `enabled` — toggle caching on/off (env: `RESPONSE_CACHE_ENABLED`)
- `cache.store` — cache driver to use (env: `RESPONSE_CACHE_DRIVER`, default: `file`)
- `cache.lifetime_in_seconds` — default cache lifetime (env: `RESPONSE_CACHE_LIFETIME`, default: 7 days)
- `cache.tag` — default tag for cache entries
- `bypass.header_name` / `bypass.header_value` — header to bypass cache for testing
- `debug.enabled` — add `X-Cache-Status`, `X-Cache-Time`, `X-Cache-Age`, `X-Cache-Key` headers
- `ignored_query_parameters` — query params excluded from cache key (UTM tags, gclid, fbclid)
- `cache_profile` — class determining what to cache
- `hasher` — class generating cache keys
- `serializer` — class serializing/unserializing responses
- `replacers` — classes replacing dynamic content in cached responses
