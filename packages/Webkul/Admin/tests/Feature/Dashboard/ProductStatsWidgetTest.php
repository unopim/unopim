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
        // The new clickable filter chips depend on this Vue helper.
        ->assertSee('productsUrl', false);
});

it('renders the Active stat as a status=true filter link', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee("productsUrl({ status: 'true' })", false);
});

it('renders the Inactive stat as a status=false filter link', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee("productsUrl({ status: 'false' })", false);
});

it('renders the type-distribution legend chips as type filter links', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        // The legend loop binds :href="productsUrl({ type })" — present in the
        // template even when the user has zero products of that type because
        // the loop iterates the typeDistribution payload from the AJAX call.
        ->assertSee('productsUrl({ type })', false);
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
        ],
    ]);
});

it('counts a configurable product that has a variant in withVariants', function () {
    // Reproduces Internal-679: the dashboard "With Variants" stat showed 0
    // even when configurable products had variants, because the helper
    // queried a `product_relations` table that is unused for variant
    // storage. Variants live on `products.parent_id` (see ProductRepository
    // line 127, ProductFactory::withVariantProduct).
    $configurable = Product::factory()
        ->configurable()
        ->withVariantProduct()
        ->create();

    expect($configurable->fresh()->variants()->count())->toBeGreaterThan(0)
        ->and(Product::where('parent_id', $configurable->id)->count())->toBeGreaterThan(0);

    Cache::forget('dashboard.product_stats');

    $stats = app(Dashboard::class)->getProductStats();

    expect($stats['withVariants'])->toBeGreaterThanOrEqual(1);
});

it('does not count a configurable product without variants in withVariants', function () {
    Product::factory()->configurable()->create();

    Cache::forget('dashboard.product_stats');

    $stats = app(Dashboard::class)->getProductStats();

    expect($stats['withVariants'])->toBe(0);
});
