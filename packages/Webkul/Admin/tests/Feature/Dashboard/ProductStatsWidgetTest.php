<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
    // The dashboard helper caches its payload for 5 minutes — clear it
    // so each test sees a fresh query result.
    Cache::forget('dashboard.product_stats');
});

it('renders the dashboard product-stats widget without errors', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('v-dashboard-product-stats', false);
});

it('renders the Active stat as a status=1 filter link', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('filters[status][]=1', false);
});

it('renders the Inactive stat as a status=0 filter link', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('filters[status][]=0', false);
});

it('renders the type-distribution legend chips as type filter links', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        // The legend loop binds :href="typeFilterUrl(type)" — the helper
        // builds /admin/catalog/products?filters[type][]=<type>. We assert
        // the helper name is present in the inlined Vue template so a
        // future refactor away from it triggers a test failure.
        ->assertSee('typeFilterUrl(type)', false);
});

it('returns the product-stats payload from the dashboard stats endpoint', function () {
    $response = $this->getJson(route('admin.dashboard.stats', ['type' => 'product-stats']));

    $response->assertOk();

    // The widget consumes these keys; refactoring the helper must not
    // change the contract the Vue component depends on.
    $response->assertJsonStructure([
        'statistics' => [
            'totalProducts',
            'statusBreakdown',
            'typeDistribution',
            'withVariants',
        ],
    ]);
});

it('counts a configurable product that has a variant in withVariants', function () {
    // Reproduces Internal-679: the dashboard "With Variants" stat showed 0
    // (or a misleading number from the unrelated product_relations table)
    // even when configurable products had real variants, because the helper
    // queried the wrong table. Variants live on products.parent_id (see
    // ProductRepository line 127, ProductFactory::withVariantProduct).
    Cache::forget('dashboard.product_stats');
    $baseline = app(Dashboard::class)->getProductStats()['withVariants'];

    $configurable = Product::factory()
        ->configurable()
        ->withVariantProduct()
        ->create();

    expect($configurable->fresh()->variants()->count())->toBeGreaterThan(0)
        ->and(Product::where('parent_id', $configurable->id)->count())->toBeGreaterThan(0);

    Cache::forget('dashboard.product_stats');
    $stats = app(Dashboard::class)->getProductStats();

    // Delta-assertion so the test works regardless of pre-existing data
    // in the shared dev database.
    expect($stats['withVariants'])->toBe($baseline + 1);
});

it('does not count a bare configurable without variants in withVariants', function () {
    Cache::forget('dashboard.product_stats');
    $baseline = app(Dashboard::class)->getProductStats()['withVariants'];

    Product::factory()->configurable()->create();

    Cache::forget('dashboard.product_stats');
    $stats = app(Dashboard::class)->getProductStats();

    // The count must not move when a configurable has no child rows.
    expect($stats['withVariants'])->toBe($baseline);
});
