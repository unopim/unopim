<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the bulk edit page when products and attributes are in session', function () {
    $products = Product::factory()->count(2)->create();

    $sku = Attribute::where('code', 'sku')->first();
    $name = Attribute::where('code', 'name')->first();

    // Set up session via filters endpoint
    $response = $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => $products->pluck('id')->toArray(),
        'filter'  => [
            'filtered_attributes' => [
                ['id' => $sku->id],
                ['id' => $name->id],
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['message', 'redirect']);

    // Now load the bulk edit page
    $this->get(route('admin.catalog.products.bulkedit'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.products.bulk-edit.action'));
});

it('should return validation error when too many products selected', function () {
    $productIds = range(1, 101);

    $response = $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => $productIds,
        'filter'  => [
            'filtered_attributes' => [['id' => 1]],
        ],
    ]);

    $response->assertUnprocessable();
});

it('should redirect when no products are in session', function () {
    $this->get(route('admin.catalog.products.bulkedit'))
        ->assertRedirect();
});

it('should fetch attributes for bulk edit modal', function () {
    $response = $this->getJson(route('admin.catalog.bulkedit.attributes.fetch-all'));

    $response->assertOk();
    $response->assertJsonStructure([
        'options',
        'page',
        'lastPage',
    ]);

    // SKU and unsupported types should not appear
    $options = collect($response->json('options'));

    $this->assertTrue($options->where('code', 'sku')->isEmpty(), 'SKU should be excluded from bulk edit attributes');
});

it('should display readable channel and locale names in column headers', function () {
    $products = Product::factory()->count(1)->create();

    $nameAttribute = Attribute::where('code', 'name')->first();

    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => $products->pluck('id')->toArray(),
        'filter'  => [
            'filtered_attributes' => [
                ['id' => $nameAttribute->id],
            ],
        ],
    ]);

    $response = $this->get(route('admin.catalog.products.bulkedit'));

    $response->assertOk();

    // If name is locale-specific, the header label should contain the locale name
    // not just the locale code
    if ($nameAttribute->value_per_locale) {
        $locale = core()->getAllActiveLocales()->first();

        if ($locale && $locale->name) {
            $content = $response->getContent();

            // The JSON headers passed to Vue should have locale name, not code
            $this->assertStringContainsString($locale->name, $content);
        }
    }
});
