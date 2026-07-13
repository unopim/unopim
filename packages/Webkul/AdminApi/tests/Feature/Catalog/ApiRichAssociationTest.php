<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Contracts\AssociationType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;

/**
 * Task 6: rich REST support.
 *
 * Proves the AdminApi product endpoints accept + return the unified
 * `associations` map (`{ <typeCode>: [ {sku, additional_data?} ] }`) —
 * custom types (e.g. `bundle_kit`) and per-link `additional_data` — for
 * create (POST), update (PUT) and partial update (PATCH), while the legacy
 * flat `values.associations.<section>` payload keeps working byte-unchanged.
 */
beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * Seeds a custom `bundle_kit_*` association type with a required numeric
 * `quantity` field. Unique code per call so tests don't collide.
 */
function seedBundleKitAssociationType(): AssociationType
{
    return app(AssociationTypeRepository::class)->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [
            [
                'code'        => 'quantity',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => 1,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Quantity'],
            ],
        ],
    ]);
}

function assertRichAssociationRow(int $productId, int $associationTypeId, int $relatedProductId, array $expectedAdditionalData): void
{
    $row = DB::table('product_associations')
        ->where('product_id', $productId)
        ->where('association_type_id', $associationTypeId)
        ->where('related_product_id', $relatedProductId)
        ->first();

    expect($row)->not->toBeNull();
    expect(json_decode($row->additional_data, true))->toBe($expectedAdditionalData);
}

it('persists a rich bundle_kit association with quantity via the PUT update API', function () {
    $bundleKitType = seedBundleKitAssociationType();

    $product = Product::factory()->simple()->create();
    $family = AttributeFamily::find($product->attribute_family_id);
    $related = Product::factory()->simple()->create();

    $payload = [
        'sku'          => $product->sku,
        'parent'       => null,
        'family'       => $family->code,
        'values'       => [
            'common' => ['sku' => $product->sku],
        ],
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['quantity' => '2']],
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $payload)
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true]);

    assertRichAssociationRow($product->id, $bundleKitType->id, $related->id, ['common' => ['quantity' => '2']]);
});

it('persists a rich bundle_kit association with quantity via the POST store (create) API', function () {
    $bundleKitType = seedBundleKitAssociationType();

    $family = AttributeFamily::first();
    $related = Product::factory()->simple()->create();

    $sku = 'API-RICH-CREATE-'.uniqid();

    $payload = [
        'sku'          => $sku,
        'parent'       => null,
        'family'       => $family->code,
        'values'       => [
            'common' => ['sku' => $sku],
        ],
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['quantity' => '5']],
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), $payload)
        ->assertStatus(201)
        ->assertJsonFragment(['success' => true]);

    $created = Product::where('sku', $sku)->firstOrFail();

    assertRichAssociationRow($created->id, $bundleKitType->id, $related->id, ['common' => ['quantity' => '5']]);
});

it('persists a rich bundle_kit association with quantity via the PATCH partial update API', function () {
    $bundleKitType = seedBundleKitAssociationType();

    $product = Product::factory()->simple()->create();
    $related = Product::factory()->simple()->create();

    $payload = [
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['quantity' => '7']],
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.products.patch', ['sku' => $product->sku]), $payload)
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true]);

    assertRichAssociationRow($product->id, $bundleKitType->id, $related->id, ['common' => ['quantity' => '7']]);
});

it('still supports the legacy flat values.associations.up_sells payload unchanged when no rich associations key is sent', function () {
    $product = Product::factory()->simple()->create();
    $family = AttributeFamily::find($product->attribute_family_id);
    $upSell = Product::factory()->simple()->create();

    $payload = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common'       => ['sku' => $product->sku],
            'associations' => [
                'up_sells' => [$upSell->sku],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $payload)
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true]);

    $upSellsType = app(AssociationTypeRepository::class)->findByCode('up_sells');

    expect(
        DB::table('product_associations')
            ->where('product_id', $product->id)
            ->where('association_type_id', $upSellsType->id)
            ->where('related_product_id', $upSell->id)
            ->exists()
    )->toBeTrue();

    $product->refresh();

    expect($product->values['associations']['up_sells'] ?? null)->toBe([$upSell->sku]);
});

it('returns the bundle_kit association with its quantity in the GET product response', function () {
    $bundleKitType = seedBundleKitAssociationType();

    $product = Product::factory()->simple()->create();
    $related = Product::factory()->simple()->create();

    app(ProductAssociationRepository::class)->syncTypeWithData($product->id, $bundleKitType->id, [
        [
            'related_product_id' => $related->id,
            'position'           => null,
            'additional_data'    => ['common' => ['quantity' => '9']],
        ],
    ]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $product->sku]))
        ->assertStatus(200);

    $associations = $response->json('associations');

    expect($associations)->toBeArray();
    expect($associations[$bundleKitType->code] ?? null)->not->toBeNull();

    $links = $associations[$bundleKitType->code];

    expect($links)->toHaveCount(1);
    expect($links[0]['related_sku'])->toBe($related->sku);
    expect($links[0]['additional_data'])->toBe(['common' => ['quantity' => '9']]);
});

it('returns a 422 (not a 500) and persists nothing when a rich association link has an invalid field value', function () {
    $bundleKitType = seedBundleKitAssociationType();

    $product = Product::factory()->simple()->create();
    $family = AttributeFamily::find($product->attribute_family_id);
    $related = Product::factory()->simple()->create();

    $originalStatus = (bool) $product->status;
    $flippedStatus = ! $originalStatus;

    $payload = [
        'sku'          => $product->sku,
        'parent'       => null,
        'family'       => $family->code,
        'status'       => $flippedStatus,
        'values'       => [
            'common' => ['sku' => $product->sku],
        ],
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['quantity' => 'not-a-number']],
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $payload)
        ->assertStatus(422)
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseCount('product_associations', 0);

    $product->refresh();

    expect((bool) $product->status)->toBe($originalStatus);
});
