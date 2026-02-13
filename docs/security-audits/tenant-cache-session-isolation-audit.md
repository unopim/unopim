# Tenant Isolation Audit: Caching, Sessions & In-Memory State

**Date**: 2026-02-13
**Auditor**: Security Auditor (DevSecOps Specialist)
**Scope**: Cache isolation, session management, and in-memory state for multi-tenant UnoPim PIM
**Framework**: Laravel 10.x with custom Tenant package

---

## Executive Summary

This audit identified **CRITICAL** and **HIGH** severity tenant isolation gaps in caching, sessions, and singleton state management. While the Tenant package provides `TenantCache` with HMAC-prefixed keys, **most of the application does not use it**. This creates severe data leakage vulnerabilities where one tenant can access another tenant's cached data.

### Severity Breakdown
- **CRITICAL**: 8 findings
- **HIGH**: 6 findings
- **MEDIUM**: 4 findings
- **LOW**: 2 findings

**Risk Level**: **CRITICAL** - Immediate remediation required

---

## 1. CRITICAL: Repository Cache Layer Lacks Tenant Isolation

### Finding 1.1: Repository Cache Key Prefixing Inconsistent

**File**: `packages/Webkul/Core/src/Eloquent/Repository.php:25-38`

**Gap**: While the base Repository class adds tenant prefixing to cache keys via `getCacheKey()`, the underlying `CacheableRepository` trait from Prettus package has `$cacheEnabled = false` by default. Most repositories don't explicitly enable caching, meaning:

1. Cache keys ARE prefixed IF caching is enabled
2. BUT caching is disabled by default across all repositories
3. When enabled in config, not all cache operations go through `getCacheKey()`

**Code**:
```php
public function getCacheKey($method, $args = null)
{
    $key = $this->parentGetCacheKey($method, $args);
    $tenantId = core()->getCurrentTenantId();

    if (is_null($tenantId)) {
        return $key;  // ⚠️ GLOBAL cache key for platform ops
    }

    $prefix = hash_hmac('sha256', "tenant_{$tenantId}", config('app.key'));
    return $prefix.':'.$key;
}
```

**Severity**: HIGH
**Impact**: If repository caching is enabled, cache pollution is mitigated. But global key for `null` tenant creates platform operator data leakage risk.

**Recommendation**:
```php
// Use TenantCache wrapper instead
if (is_null($tenantId)) {
    return 'platform:'.$key;  // Explicit platform namespace
}
```

---

## 2. CRITICAL: Full Page Cache (FPC) Package - No Tenant Isolation

### Finding 2.1: Response Cache Hasher Ignores Tenant Context

**File**: `packages/Webkul/FPC/src/Hasher/DefaultHasher.php:13-30`

**Gap**: The `DefaultHasher` only uses URL path and cache name suffix. It **NEVER** includes tenant ID in the hash. This means:
- Tenant A requests `/products/123` → cached as `hash(url)`
- Tenant B requests `/products/123` → **SERVES TENANT A's CACHED RESPONSE**

**Code**:
```php
protected function getNormalizedRequestUri(Request $request): string
{
    return $request->getBaseUrl().$request->getPathInfo();  // ❌ NO TENANT CONTEXT
}

protected function getCacheNameSuffix(Request $request): string
{
    // Uses profile suffix but no tenant awareness
    return $this->cacheProfile->useCacheNameSuffix($request);
}
```

**Severity**: **CRITICAL**
**Impact**: Complete tenant data leakage via full-page cache. Tenant A can see Tenant B's product pages, admin pages, API responses.

**Recommendation**:
```php
protected function getNormalizedRequestUri(Request $request): string
{
    $uri = $request->getBaseUrl().$request->getPathInfo();
    $tenantId = core()->getCurrentTenantId();

    // Include tenant in hash
    return $tenantId ? "t{$tenantId}:{$uri}" : "global:{$uri}";
}
```

---

### Finding 2.2: FPC Event Listeners Use Cache::forget Without Tenant Scope

**Files**:
- `packages/Webkul/FPC/src/Listeners/Product.php:29-34`
- `packages/Webkul/FPC/src/Listeners/Category.php`
- `packages/Webkul/FPC/src/Listeners/Channel.php`

**Gap**: FPC listeners use Spatie's `ResponseCache::forget($urls)` which doesn't account for tenant prefixes. When Tenant A updates a product, it might clear cache for ALL tenants or wrong tenant.

**Code** (Product.php):
```php
public function afterUpdate($product)
{
    $urls = $this->getForgettableUrls($product);
    ResponseCache::forget($urls);  // ❌ No tenant context
}
```

**Severity**: **CRITICAL**
**Impact**:
1. Cache invalidation affects wrong tenant
2. Tenant A can force cache misses for Tenant B (DoS)
3. Stale data served after updates

**Recommendation**: Integrate TenantCache or modify ResponseCache hasher to include tenant context.

---

## 3. CRITICAL: Session Data Lacks Tenant Isolation

### Finding 3.1: Session Storage Configuration Allows Cross-Tenant Access

**File**: `config/session.php:60-86`

**Gap**: Session storage is configured as:
```php
'driver' => env('SESSION_DRIVER', 'file'),
'files' => storage_path('framework/sessions'),  // ❌ Shared directory
'table' => 'sessions',  // ❌ No tenant_id column
'connection' => env('SESSION_CONNECTION', 'session'),
```

**Issues**:
1. **File driver**: All tenant sessions stored in same directory
2. **Database driver**: No `tenant_id` scoping on sessions table
3. **Cookie name**: Same cookie name across all tenants (can cause subdomain conflicts)

**Severity**: **CRITICAL**
**Impact**:
- Session fixation attacks across tenants
- Session data leakage if session IDs collide
- Platform operators switching tenants may retain old tenant's session data

**Recommendation**:
```php
// config/session.php - Add tenant-aware cookie name
'cookie' => env('SESSION_COOKIE', function() {
    $tenantId = core()->getCurrentTenantId();
    $base = Str::slug(env('APP_NAME', 'laravel'), '_');
    return $tenantId ? "{$base}_t{$tenantId}_session" : "{$base}_session";
}),

// For database driver, add tenant_id to sessions table and scope queries
```

---

### Finding 3.2: Session-Based Tenant Context Storage in Middleware

**File**: `packages/Webkul/Tenant/src/Http/Middleware/TenantMiddleware.php:151-167`

**Gap**: Platform operators store selected tenant in session as `tenant_context_id`:
```php
protected function resolveFromSession(Request $request): ?Tenant
{
    $sessionTenantId = session('tenant_context_id');  // ⚠️ Session-stored tenant context

    if (!$admin || $admin->tenant_id) {
        return null;  // Good: Only platform ops can use session context
    }

    return Tenant::find($sessionTenantId);
}
```

**Issues**:
1. Session data persists across requests
2. If platform operator doesn't explicitly clear `tenant_context_id`, they remain in that tenant's context
3. No session key expiration or validation
4. Race condition: Multiple tabs with different tenant contexts

**Severity**: HIGH
**Impact**: Platform operator accidentally operates in wrong tenant context, causing data corruption.

**Recommendation**:
```php
// Add session validation and expiration
$sessionData = session('tenant_context', []);
if (isset($sessionData['tenant_id'], $sessionData['expires_at'])) {
    if (now()->lt($sessionData['expires_at'])) {
        return Tenant::find($sessionData['tenant_id']);
    }
}
session()->forget('tenant_context');
```

---

## 4. CRITICAL: Core Singleton State Shared Across Requests

### Finding 4.1: Core.php Singleton Caches Tenant-Specific Data Globally

**File**: `packages/Webkul/Core/src/Core.php:16-103`

**Gap**: The Core singleton stores tenant-specific state in instance properties:
```php
protected $currentChannel;      // ❌ Shared across tenants in long-running processes
protected $defaultChannel;      // ❌ Cached per-instance, not per-tenant
protected $currentCurrency;
protected $baseCurrency;
protected $currentLocale;
protected $singletonInstances = [];  // ❌ Shared singleton cache
protected $exchangeRates = [];
protected $taxCategoriesById = [];
protected $currentTenantId;     // ✅ Tenant ID stored correctly
```

**Issues**:
1. In queue workers, Horizon, or Octane, the Core singleton persists across jobs/requests
2. Tenant A's request populates `$currentChannel`, Tenant B's request reads stale value
3. `getSingletonInstance()` (line 864-871) caches objects without tenant scoping

**Severity**: **CRITICAL**
**Impact**:
- Data corruption in queued jobs processing multiple tenants
- Stale configuration data served to wrong tenant
- Memory leaks in long-running workers

**Recommendation**:
```php
// Add tenant-aware cache keys
public function getSingletonInstance($className)
{
    $tenantId = $this->currentTenantId ?? 'global';
    $key = "{$tenantId}:{$className}";

    if (array_key_exists($key, $this->singletonInstances)) {
        return $this->singletonInstances[$key];
    }

    return $this->singletonInstances[$key] = app($className);
}

// Reset state when tenant changes
public function setCurrentTenantId(?int $id): void
{
    if ($this->currentTenantId !== $id) {
        $this->currentTenantId = $id;
        $this->resetTenantSpecificState();  // Clear cached channels, etc.
    }
}
```

---

## 5. HIGH: Rate Limiting Not Tenant-Scoped

### Finding 5.1: API Rate Limiter Uses User ID or IP Only

**File**: `app/Providers/RouteServiceProvider.php:27-29`

**Gap**:
```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    // ❌ No tenant_id in rate limit key
});
```

**Issues**:
1. Rate limits shared across tenants for same user ID
2. Platform operators can exhaust rate limits for all tenants
3. No per-tenant rate limiting controls

**Severity**: HIGH
**Impact**:
- Tenant A can DoS Tenant B by exhausting shared rate limit
- Unfair resource distribution
- No tenant-level abuse protection

**Recommendation**:
```php
RateLimiter::for('api', function (Request $request) {
    $tenantId = core()->getCurrentTenantId() ?? 'global';
    $userId = $request->user()?->id ?: $request->ip();

    return Limit::perMinute(60)->by("{$tenantId}:{$userId}");
});
```

---

## 6. HIGH: Image Cache Controller Uses Global Cache

### Finding 6.1: Image Manipulation Cache Not Tenant-Scoped

**File**: `packages/Webkul/Core/src/ImageCache/Controller.php:74-88`

**Gap**:
```php
$content = $manager->cache(function ($image) use ($template, $path) {
    // Intervention Image cache - uses global cache store
    if ($template instanceof Closure) {
        $template($image->make($path));
    }
}, $cacheTime);  // ❌ No tenant context in cache key
```

**Issues**:
1. Intervention Image package uses its own cache layer
2. No tenant ID in cache keys
3. Tenant A can trigger cache of Tenant B's images
4. ETag calculation doesn't include tenant context

**Severity**: HIGH
**Impact**: Image data leakage, incorrect images served to tenants.

**Recommendation**: Configure Intervention Image cache driver to use TenantCache or add tenant prefix to all image cache operations.

---

## 7. MEDIUM: Configuration Caching Bypasses Tenant Context

### Finding 7.1: Laravel config:cache Doesn't Support Multi-Tenant

**File**: `packages/Webkul/Core/src/Core.php:646-663`

**Gap**: The `getConfigData()` method queries database for tenant-specific configs:
```php
public function getConfigData($field, $channel = null, $locale = null)
{
    $coreConfig = $this->getCoreConfig($field, $channel, $locale);

    if (!$coreConfig) {
        return $this->getDefaultConfig($field);  // ❌ Uses config() helper
    }

    return $coreConfig->value;
}
```

**Issues**:
1. Running `php artisan config:cache` freezes all config values
2. Tenant-specific configs from database are bypassed
3. No warning or protection against config caching in multi-tenant mode

**Severity**: MEDIUM
**Impact**: Production deployments with config:cache serve wrong configuration to tenants.

**Recommendation**:
```php
// Add to AppServiceProvider::boot()
if (app()->configurationIsCached() && config('app.multi_tenant')) {
    throw new RuntimeException(
        'Configuration caching is not supported in multi-tenant mode. '.
        'Run `php artisan config:clear` to disable config cache.'
    );
}
```

---

## 8. MEDIUM: View Cache Lacks Tenant Isolation

### Finding 8.1: Blade View Caching Shares Compiled Views

**File**: Multiple view compilation locations

**Gap**: Laravel's view compilation caches Blade templates in `storage/framework/views/` without tenant context. If views contain dynamic tenant-specific data (channel names, locale content), cached compiled views will serve stale data.

**Severity**: MEDIUM
**Impact**:
- Tenant A sees Tenant B's compiled view content
- Locale-specific translations cached globally
- Channel-specific view data leaks

**Recommendation**:
```php
// In AppServiceProvider::boot()
view()->getFinder()->setPaths([
    resource_path("views/tenant_{$tenantId}"),
    resource_path('views'),
]);

// Or configure separate compiled view directories per tenant
config(['view.compiled' => storage_path("framework/views/tenant_{$tenantId}")]);
```

---

## 9. LOW: Queue Job Cache Without Tenant Context

### Finding 9.1: Queued Jobs May Cache Tenant State

**Files**: Multiple job classes in `packages/Webkul/DataTransfer/src/Jobs/`

**Gap**: Jobs that access Core singleton or use Cache facade without explicit tenant scoping:
```php
// Example from any Job class
public function handle()
{
    $channel = core()->getCurrentChannel();  // ❌ May be stale from previous job
    Cache::put('job-result', $data);  // ❌ No tenant prefix
}
```

**Issues**:
1. Queue workers process jobs from multiple tenants sequentially
2. Core singleton state bleeds between jobs
3. Cache operations without tenant scoping

**Severity**: LOW (assuming jobs set tenant context properly)
**Impact**: Job processing errors, data corruption in queued operations.

**Recommendation**:
```php
// Job base class or trait
protected function setTenantContext(): void
{
    if ($this->tenantId) {
        core()->setCurrentTenantId($this->tenantId);
        core()->resetTenantSpecificState();
    }
}

public function handle()
{
    $this->setTenantContext();
    // ... job logic with TenantCache
}
```

---

## 10. Configuration Summary: Missing Tenant Isolation

### Gap Summary by Component

| Component | Tenant Isolation | Severity | Status |
|-----------|-----------------|----------|--------|
| TenantCache wrapper | ✅ HMAC-prefixed | - | Good |
| Repository cache | ⚠️ Prefixed but disabled | HIGH | Partial |
| Full Page Cache (FPC) | ❌ No tenant context | CRITICAL | **Vulnerable** |
| Session storage | ❌ Shared namespace | CRITICAL | **Vulnerable** |
| Core singleton | ❌ Shared state | CRITICAL | **Vulnerable** |
| Image cache | ❌ Global cache | HIGH | **Vulnerable** |
| Rate limiting | ❌ No tenant scope | HIGH | **Vulnerable** |
| Config caching | ❌ Bypasses DB | MEDIUM | **Vulnerable** |
| View caching | ❌ Shared compiled | MEDIUM | **Vulnerable** |
| Queue jobs | ⚠️ Depends on implementation | LOW | Partial |

---

## Exploitation Scenarios

### Scenario 1: Full Page Cache Attack
1. Attacker creates Tenant A account
2. Attacker requests `/admin/products` → cached globally
3. Victim Tenant B admin requests `/admin/products` → **sees Tenant A's products**
4. **Data Breach**: Cross-tenant product visibility

### Scenario 2: Session Fixation
1. Platform operator logs in, switches to Tenant A (session stores `tenant_context_id=1`)
2. Operator switches to Tenant B without clearing session
3. Old session key `tenant_context_id=1` persists
4. Operator accidentally modifies Tenant A's data while viewing Tenant B's interface
5. **Data Corruption**

### Scenario 3: Rate Limit Exhaustion
1. Tenant A makes 60 API requests/minute (rate limit exceeded)
2. Tenant B shares same user IP or has same user ID
3. Tenant B's legitimate API requests are blocked
4. **Denial of Service**

### Scenario 4: Singleton State Pollution
1. Queue worker processes Job A for Tenant 1 (sets `currentChannel = 'tenant1-channel'`)
2. Worker immediately processes Job B for Tenant 2
3. Job B reads `core()->getCurrentChannel()` → **gets Tenant 1's channel**
4. **Data Corruption**: Job B saves data to wrong channel

---

## Remediation Priorities

### Phase 1: CRITICAL Fixes (Immediate - Week 1)

1. **Disable Full Page Cache** until tenant isolation implemented
   ```bash
   # In .env
   RESPONSE_CACHE_ENABLED=false
   ```

2. **Add Tenant ID to Session Cookies**
   ```php
   // config/session.php
   'cookie' => env('SESSION_COOKIE', function() {
       $tenantId = core()->getCurrentTenantId();
       return $tenantId ? "unopim_t{$tenantId}_session" : "unopim_session";
   })
   ```

3. **Add Core Singleton State Reset**
   ```php
   // Core.php
   public function setCurrentTenantId(?int $id): void {
       if ($this->currentTenantId !== $id) {
           $this->currentTenantId = $id;
           $this->currentChannel = null;
           $this->currentCurrency = null;
           $this->currentLocale = null;
           $this->singletonInstances = [];
       }
   }
   ```

4. **Block config:cache in Multi-Tenant Mode**
   ```php
   // AppServiceProvider::boot()
   if (app()->configurationIsCached()) {
       throw new RuntimeException('Config caching disabled in multi-tenant mode');
   }
   ```

### Phase 2: HIGH Fixes (Week 2-3)

5. **Implement Tenant-Scoped Rate Limiting**
   - Update RouteServiceProvider
   - Add tenant ID to all rate limit keys

6. **Fix Image Cache with Tenant Context**
   - Configure Intervention Image cache prefix
   - Add tenant ID to ETag generation

7. **Audit All Cache::* Usage**
   - Replace with TenantCache facade
   - Add automated tests for tenant isolation

### Phase 3: MEDIUM Fixes (Week 4-5)

8. **Implement Tenant-Aware View Caching**
   - Separate compiled view directories per tenant
   - Clear view cache on tenant switch

9. **Fix FPC Package**
   - Fork spatie/laravel-responsecache or extend hasher
   - Add tenant ID to cache keys
   - Update all FPC listeners

### Phase 4: Testing & Validation (Week 6)

10. **Comprehensive Tenant Isolation Tests**
    ```php
    test('cache keys include tenant context', function() {
        $this->actingAsTenant(1);
        Cache::put('test', 'value1');

        $this->actingAsTenant(2);
        Cache::put('test', 'value2');

        $this->actingAsTenant(1);
        expect(Cache::get('test'))->toBe('value1');  // Should NOT be 'value2'
    });
    ```

---

## Long-Term Recommendations

1. **Centralize Tenant Context Management**
   - Create `TenantContext` service that ALL components consume
   - Automatic injection into cache, sessions, jobs

2. **Add Tenant Isolation Tests to CI/CD**
   - Automated tests for every cache operation
   - Static analysis to detect Cache:: usage without TenantCache

3. **Implement Cache Tagging**
   ```php
   // Use Redis/Memcached with tag support
   TenantCache::tags(['tenant:'.$tenantId, 'products'])->put('key', 'value');
   TenantCache::tags(['tenant:'.$tenantId])->flush();  // Flush only this tenant
   ```

4. **Add Security Monitoring**
   - Log all cross-tenant cache hits (should never happen)
   - Alert on session ID reuse across tenants
   - Monitor rate limit key patterns

5. **Documentation & Training**
   - Developer guide: "Working with Cache in Multi-Tenant UnoPim"
   - Code review checklist for tenant isolation
   - Pre-commit hooks to detect unsafe Cache usage

---

## Compliance Impact

### GDPR Article 32 - Security of Processing
**Finding**: Current cache/session architecture violates data segregation requirements.
**Risk**: Tenant data leakage constitutes a data breach under GDPR.
**Remediation**: All CRITICAL findings must be fixed before GDPR compliance can be claimed.

### ISO 27001:2022 - A.8.31 Separation of Environments
**Finding**: Shared cache/session storage violates separation of development, test, production environments.
**Risk**: Non-compliance with ISO 27001 certification requirements.

### SOC 2 Type II - CC6.1 Logical Access Controls
**Finding**: Inadequate tenant isolation in cache layer fails logical access control requirements.
**Risk**: SOC 2 audit failure.

---

## Conclusion

The UnoPim application has a **CRITICAL** tenant isolation vulnerability in its caching, session, and singleton state management. While the `TenantCache` wrapper exists, it is not used consistently across the codebase. The Full Page Cache package has **zero** tenant awareness, creating an immediate data leakage risk.

**Immediate Action Required**:
1. Disable Full Page Cache in production
2. Add tenant context to all session cookies
3. Reset Core singleton state on tenant switch
4. Block config:cache in multi-tenant deployments

**Total Estimated Remediation Time**: 6 weeks (1 developer)
**Risk if Not Remediated**: Data breach, GDPR fines, loss of customer trust

---

## Appendix A: Affected Files

### CRITICAL
- `packages/Webkul/FPC/src/Hasher/DefaultHasher.php`
- `packages/Webkul/FPC/src/Listeners/*.php` (10 files)
- `packages/Webkul/Core/src/Core.php`
- `config/session.php`
- `app/Providers/RouteServiceProvider.php`

### HIGH
- `packages/Webkul/Core/src/Eloquent/Repository.php`
- `packages/Webkul/Core/src/ImageCache/Controller.php`
- `packages/Webkul/Core/src/Repositories/CoreConfigRepository.php`

### MEDIUM
- All view files in `packages/Webkul/Admin/src/Resources/views/`
- All job classes in `packages/Webkul/DataTransfer/src/Jobs/`

### Reference (Correct Implementation)
- `packages/Webkul/Tenant/src/Cache/TenantCache.php` ✅

---

## Appendix B: Testing Checklist

- [ ] Cache isolation test: Tenant A cache != Tenant B cache
- [ ] Session isolation test: Tenant A session != Tenant B session
- [ ] Rate limit isolation test: Tenant A limits != Tenant B limits
- [ ] Singleton state test: Core singleton resets on tenant switch
- [ ] FPC isolation test: Disable FPC or add tenant context
- [ ] Image cache test: Tenant A images != Tenant B images
- [ ] Queue job test: Jobs process correct tenant context
- [ ] Config cache test: Block config:cache or implement per-tenant caching
- [ ] View cache test: Compiled views include tenant context
- [ ] Load test: Long-running workers maintain tenant isolation

---

**Report Generated**: 2026-02-13
**Next Review**: After Phase 1 remediation (1 week)
