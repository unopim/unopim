<?php

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
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

it('fires catalog.product.update.after for every product saved by bulk edit', function () {
    $products = Product::factory()->count(2)->create();

    Event::fake(['catalog.product.update.after', 'catalog.product.bulk.edit.after']);

    // Sync queue in the test env runs BulkProductUpdate inline, so the event
    // fires within this request. Payload mirrors what the bulk-edit Vue
    // spreadsheet posts: { product_id: { attribute_code: value } }.
    $payload = [];
    foreach ($products as $product) {
        $payload[$product->id] = ['sku' => $product->sku];
    }

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), ['data' => $payload])
        ->assertOk();

    Event::assertDispatched('catalog.product.update.after', count($products));

    // One bulk event is dispatched carrying all processed product IDs.
    // The payload is ['ids' => [...]], matching how call_user_func_array passes it.
    Event::assertDispatched('catalog.product.bulk.edit.after', function ($event, $payload) use ($products) {
        $ids = $payload['ids'] ?? [];

        return count(array_intersect($products->pluck('id')->toArray(), $ids)) === $products->count();
    });
});

it('should fetch only attributes belonging to the selected products families', function () {
    // Helper to create a family with a linked attribute group and attribute
    $makeFamily = function (Attribute $attr): AttributeFamily {
        $group = AttributeGroup::factory()->create();
        $family = AttributeFamily::factory()->create();
        $family->familyGroups()->attach($group);
        $mapping = $family->attributeFamilyGroupMappings()->first();
        $mapping->customAttributes()->attach($attr);

        return $family;
    };

    $attrA = Attribute::factory()->create(['type' => 'text']);
    $familyA = $makeFamily($attrA);

    $attrB = Attribute::factory()->create(['type' => 'text']);
    $familyB = $makeFamily($attrB);

    // Create one product per family
    $productA = Product::factory()->create(['attribute_family_id' => $familyA->id]);
    $productB = Product::factory()->create(['attribute_family_id' => $familyB->id]);

    // Populate session via the filters endpoint (mirrors real usage)
    $this->postJson(route('admin.catalog.products.bulkedit.filters'), [
        'indices' => [$productA->id],
        'filter'  => [],
    ])->assertOk();

    $response = $this->getJson(route('admin.catalog.bulkedit.attributes.fetch-all'));
    $response->assertOk();

    $codes = collect($response->json('options'))->pluck('code')->toArray();

    // attrA should appear (it belongs to productA's family)
    expect($codes)->toContain($attrA->code);

    // attrB must NOT appear (it belongs to a different family not selected)
    expect($codes)->not->toContain($attrB->code);
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
