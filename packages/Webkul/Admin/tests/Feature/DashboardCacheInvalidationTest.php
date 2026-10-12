<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;

it('busts the catalog totals cache when a category is created (dashboard updates instantly)', function () {
    Cache::put('dashboard.total_catalogs', ['totalCategories' => 0, 'totalProducts' => 0], 300);

    Category::factory()->create();

    expect(Cache::has('dashboard.total_catalogs'))->toBeFalse();
});

it('busts the catalog totals cache when a category is deleted', function () {
    $category = Category::factory()->create();

    Cache::put('dashboard.total_catalogs', ['totalCategories' => 1, 'totalProducts' => 0], 300);

    $category->delete();

    expect(Cache::has('dashboard.total_catalogs'))->toBeFalse();
});

it('busts the configuration totals cache when a configuration entity changes', function (Closure $factory) {
    Cache::put('dashboard.total_configurations', ['x' => 1], 300);

    $factory()->create();

    expect(Cache::has('dashboard.total_configurations'))->toBeFalse();
})->with([
    'attribute'        => [fn () => Attribute::factory()],
    'attribute group'  => [fn () => AttributeGroup::factory()],
    'attribute family' => [fn () => AttributeFamily::factory()],
    'locale'           => [fn () => Locale::factory()],
    'channel'          => [fn () => Channel::factory()],
    'currency'         => [fn () => Currency::factory()],
]);
