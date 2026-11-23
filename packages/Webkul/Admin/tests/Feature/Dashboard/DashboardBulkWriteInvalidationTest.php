<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Admin\Listeners\DashboardCache;
use Webkul\Admin\Providers\EventServiceProvider;

it('drops every dashboard cache entry when flushed', function () {
    $keys = [
        'dashboard.total_catalogs',
        'dashboard.total_configurations',
        'dashboard.product_stats',
        'dashboard.needs_attention',
        'dashboard.channel_readiness',
    ];

    foreach ($keys as $key) {
        Cache::put($key, ['stale' => true], 3600);
    }

    app(DashboardCache::class)->flush();

    foreach ($keys as $key) {
        expect(Cache::has($key))->toBeFalse("{$key} survived the flush");
    }
});

it('drops only the product figures after a completeness recalculation', function () {
    foreach ([...Dashboard::PRODUCT_CACHE_KEYS, 'dashboard.total_configurations'] as $key) {
        Cache::put($key, ['stale' => true], 3600);
    }

    app(DashboardCache::class)->flushProducts();

    foreach (Dashboard::PRODUCT_CACHE_KEYS as $key) {
        expect(Cache::has($key))->toBeFalse("{$key} survived the flush");
    }

    expect(Cache::has('dashboard.total_configurations'))->toBeTrue();
});

it('flushes only the product figures after a completeness recalculation', function () {
    $listeners = (new EventServiceProvider(app()))->listens()['completeness.product.calculate.after'] ?? [];

    expect($listeners)->toBe([[DashboardCache::class, 'flushProducts']]);
});

it('flushes the dashboard cache after a bulk write outside eloquent', function (string $event) {
    $listeners = (new EventServiceProvider(app()))->listens()[$event] ?? [];

    expect($listeners)->toContain([DashboardCache::class, 'flush']);
})->with([
    'import completed'        => 'data_transfer.imports.completed',
    'import cancelled'        => 'data_transfer.imports.cancelled',
    'bulk product edit'       => 'catalog.product.bulk.edit.after',
    'demo data seeded'        => 'installer.demo_data.seeded',
]);
