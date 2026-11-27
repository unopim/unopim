<?php

use Illuminate\Support\Facades\Cache;
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

it('flushes the dashboard cache after a bulk write outside eloquent', function (string $event) {
    $listeners = (new EventServiceProvider(app()))->listens()[$event] ?? [];

    expect($listeners)->toContain([DashboardCache::class, 'flush']);
})->with([
    'import completed'        => 'data_transfer.imports.completed',
    'import cancelled'        => 'data_transfer.imports.cancelled',
    'bulk product edit'       => 'catalog.product.bulk.edit.after',
    'demo data seeded'        => 'installer.demo_data.seeded',
    'completeness calculated' => 'completeness.product.calculate.after',
]);
