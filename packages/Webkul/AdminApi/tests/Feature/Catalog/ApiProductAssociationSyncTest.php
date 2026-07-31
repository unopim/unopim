<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

/**
 * Task 6 / Gap 1 (AdminApi programmatic path):
 *
 * `ProductController::updateProduct()` and `::patchProduct()` persist product
 * `values` directly on the Eloquent model (`$product->update($data)` /
 * `$product->saveOrFail()`), bypassing `AbstractType::update()` entirely.
 * That means the `syncAssociationLinks()` dual-write introduced in Task 4
 * never ran for API-driven saves. These tests prove the `product_associations`
 * link table is now mirrored for the REST create/update/patch paths too.
 */
beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function assertAssociationLinkExists(int $productId, string $typeCode, int $relatedProductId): void
{
    expect(
        DB::table('product_associations')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->where('product_associations.product_id', $productId)
            ->where('association_types.code', $typeCode)
            ->where('product_associations.related_product_id', $relatedProductId)
            ->exists()
    )->toBeTrue("Expected a product_associations row for product {$productId}, type {$typeCode}, related {$relatedProductId}.");
}

it('dual-writes associations to the link table via the PUT update API', function () {
    $product = Product::factory()->simple()->create();

    $family = AttributeFamily::where('id', $product->attribute_family_id);
    $attribute = Attribute::factory()->create(['value_per_locale' => false, 'value_per_channel' => false, 'type' => 'text']);
    $family->first()->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $related = Product::factory()->simple()->create();
    $upSell = Product::factory()->simple()->create();
    $crossSell = Product::factory()->simple()->create();

    $updatedProduct = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->first()->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'associations' => [
                'related_products' => [$related->sku],
                'up_sells'         => [$upSell->sku],
                'cross_sells'      => [$crossSell->sku],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $updatedProduct['sku']]), $updatedProduct)
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseCount('product_associations', 3);

    assertAssociationLinkExists($product->id, 'related_products', $related->id);
    assertAssociationLinkExists($product->id, 'up_sells', $upSell->id);
    assertAssociationLinkExists($product->id, 'cross_sells', $crossSell->id);
});

it('dual-writes associations to the link table via the POST store (create) API', function () {
    $family = AttributeFamily::first();

    $related = Product::factory()->simple()->create();

    $sku = 'API-CREATE-'.uniqid();

    $payload = [
        'sku'    => $sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
            ],
            'associations' => [
                'related_products' => [$related->sku],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), $payload)
        ->assertStatus(201)
        ->assertJsonFragment(['success' => true]);

    $created = Product::where('sku', $sku)->firstOrFail();

    $this->assertDatabaseCount('product_associations', 1);

    assertAssociationLinkExists($created->id, 'related_products', $related->id);
});

it('dual-writes associations to the link table via the PATCH partial update API', function () {
    $product = Product::factory()->simple()->create();
    $family = AttributeFamily::where('id', $product->attribute_family_id)->first();
    $attribute = Attribute::factory()->create(['value_per_locale' => false, 'value_per_channel' => false, 'type' => 'text']);
    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $related = Product::factory()->simple()->create();

    $updatedProductData = [
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'associations' => [
                'related_products' => [$related->sku],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.products.patch', ['sku' => $product->sku]), $updatedProductData)
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseCount('product_associations', 1);

    assertAssociationLinkExists($product->id, 'related_products', $related->id);
});
