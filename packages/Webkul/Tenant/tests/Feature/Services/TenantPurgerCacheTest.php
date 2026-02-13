<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Tenant\Cache\TenantCache;
use Webkul\Tenant\Services\TenantPurger;

/*
|--------------------------------------------------------------------------
| Story 6.5: TenantPurger cache clearing wired to TenantCache::flush()
|--------------------------------------------------------------------------
|
| Verifies that tenant deletion triggers cache clearing via TenantCache.
|
*/

it('clears tenant cache keys during purge', function () {
    // Store a cache value for Tenant A
    TenantCache::put('test-key', 'test-value', 3600, $this->tenantA->id);
    expect(TenantCache::get('test-key', null, $this->tenantA->id))->toBe('test-value');

    // Run the purger
    $purger = app(TenantPurger::class);
    $report = $purger->purge($this->tenantA);

    // Cache clearing should report at least 1
    expect($report['cache']['keys_cleared'])->toBe(1);
});

it('does not clear other tenant cache during purge', function () {
    // Store cache values for both tenants
    TenantCache::put('shared-key', 'value-a', 3600, $this->tenantA->id);
    TenantCache::put('shared-key', 'value-b', 3600, $this->tenantB->id);

    // Purge Tenant A
    $purger = app(TenantPurger::class);
    $purger->purge($this->tenantA);

    // Tenant B's cache should still exist
    expect(TenantCache::get('shared-key', null, $this->tenantB->id))->toBe('value-b');
});
