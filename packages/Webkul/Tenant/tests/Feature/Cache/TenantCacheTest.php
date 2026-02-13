<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Tenant\Cache\TenantCache;
use Webkul\Tenant\Models\Tenant;

it('generates different HMAC prefixes for different tenants', function () {
    $prefix1 = TenantCache::prefix(1);
    $prefix2 = TenantCache::prefix(2);

    expect($prefix1)->not->toBe($prefix2);
    expect(strlen($prefix1))->toBe(64); // SHA-256 hex output
    expect(strlen($prefix2))->toBe(64);
});

it('returns global prefix when no tenant context', function () {
    core()->setCurrentTenantId(null);

    $prefix = TenantCache::prefix();
    expect($prefix)->toBe('global');
});

it('generates consistent prefix for same tenant', function () {
    $prefix1 = TenantCache::prefix(42);
    $prefix2 = TenantCache::prefix(42);

    expect($prefix1)->toBe($prefix2);
});

it('creates tenant-scoped cache keys', function () {
    $key = TenantCache::key('products.list', 1);

    expect($key)->toContain(':products.list');
    expect(strlen(explode(':', $key)[0]))->toBe(64);
});

it('stores and retrieves from tenant-scoped cache', function () {
    TenantCache::put('test_key', 'test_value', 60, 1);

    $value = TenantCache::get('test_key', null, 1);
    expect($value)->toBe('test_value');

    // Different tenant should not see the value
    $otherValue = TenantCache::get('test_key', null, 2);
    expect($otherValue)->toBeNull();
});

it('forgets tenant-scoped cache entries', function () {
    TenantCache::put('forget_key', 'forget_value', 60, 1);
    expect(TenantCache::get('forget_key', null, 1))->toBe('forget_value');

    TenantCache::forget('forget_key', 1);
    expect(TenantCache::get('forget_key', null, 1))->toBeNull();
});

it('prefixes are opaque and do not reveal tenant IDs', function () {
    $prefix = TenantCache::prefix(123);

    // Should not contain the literal tenant ID
    expect($prefix)->not->toContain('123');
    expect($prefix)->not->toContain('tenant');
});
