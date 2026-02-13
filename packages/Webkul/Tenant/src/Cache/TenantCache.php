<?php

namespace Webkul\Tenant\Cache;

use Illuminate\Support\Facades\Cache;

class TenantCache
{
    /**
     * Generate an HMAC-based opaque cache prefix for a tenant.
     * This prevents tenant ID enumeration through cache key inspection.
     */
    public static function prefix(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? core()->getCurrentTenantId();

        if (is_null($tenantId)) {
            return 'global';
        }

        return hash_hmac('sha256', "tenant_{$tenantId}", config('app.key'));
    }

    /**
     * Generate a fully-qualified tenant-aware cache key.
     */
    public static function key(string $key, ?int $tenantId = null): string
    {
        return static::prefix($tenantId).':'.$key;
    }

    /**
     * Get a value from the tenant-scoped cache.
     */
    public static function get(string $key, mixed $default = null, ?int $tenantId = null): mixed
    {
        return Cache::get(static::key($key, $tenantId), $default);
    }

    /**
     * Put a value in the tenant-scoped cache.
     */
    public static function put(string $key, mixed $value, mixed $ttl = null, ?int $tenantId = null): bool
    {
        return Cache::put(static::key($key, $tenantId), $value, $ttl);
    }

    /**
     * Remove a value from the tenant-scoped cache.
     */
    public static function forget(string $key, ?int $tenantId = null): bool
    {
        return Cache::forget(static::key($key, $tenantId));
    }

    /**
     * Flush all cache entries for a specific tenant.
     * Uses tag-based flushing if supported, otherwise no-op.
     */
    public static function flush(?int $tenantId = null): void
    {
        $prefix = static::prefix($tenantId);

        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags([$prefix])->flush();
        }
    }
}
