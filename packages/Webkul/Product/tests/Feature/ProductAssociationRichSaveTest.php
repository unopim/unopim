<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->productRepository = app(ProductRepository::class);
    $this->associationTypeRepository = app(AssociationTypeRepository::class);
    $this->associationTypeFieldRepository = app(AssociationTypeFieldRepository::class);
});

/**
 * Adds a required numeric `quantity` field to the given (existing) association
 * type, so links submitted for it must carry a valid `additional_data.common.quantity`.
 */
function addQuantityFieldToAssociationType(int $associationTypeId): void
{
    app(AssociationTypeFieldRepository::class)->create([
        'association_type_id' => $associationTypeId,
        'code'                => 'quantity',
        'type'                => 'text',
        'validation'          => 'number',
        'is_required'         => 1,
        'status'              => 1,
        'section'             => 'left',
        'en_US'               => ['name' => 'Quantity'],
    ]);
}

/**
 * Seeds a custom `bundle_kit` association type with a required numeric
 * `quantity` field, per the brief.
 */
function createBundleKitAssociationType(): int
{
    $type = app(AssociationTypeRepository::class)->create([
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

    return $type->id;
}

it('validates and rich-syncs a unified associations payload, mirroring legacy sections to JSON', function () {
    $upSellsType = $this->associationTypeRepository->findByCode('up_sells');

    addQuantityFieldToAssociationType($upSellsType->id);

    $bundleKitTypeId = createBundleKitAssociationType();
    $bundleKitType = $this->associationTypeRepository->find($bundleKitTypeId);

    $source = Product::factory()->create();
    $a = Product::factory()->create();
    $b = Product::factory()->create();

    $updated = $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            'up_sells' => [
                [
                    'sku'             => $a->sku,
                    'additional_data' => ['common' => ['quantity' => '2']],
                ],
            ],
            $bundleKitType->code => [
                [
                    'sku'             => $b->sku,
                    'additional_data' => ['common' => ['quantity' => '3']],
                ],
            ],
        ],
    ], $source->id);

    // (a1) legacy JSON back-compat: derived sku list is written exactly like the flat path.
    expect($updated->values['associations']['up_sells'] ?? null)->toBe([$a->sku]);

    // (a2) rich sync: up_sells row carries the submitted quantity.
    $upSellsRow = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $upSellsType->id)
        ->first();

    expect($upSellsRow)->not->toBeNull();
    expect(json_decode($upSellsRow->additional_data, true))->toBe(['common' => ['quantity' => '2']]);

    // (a3) rich sync: bundle_kit (a non-legacy, custom type) row carries its quantity.
    $bundleKitRow = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $bundleKitType->id)
        ->first();

    expect($bundleKitRow)->not->toBeNull();
    expect(json_decode($bundleKitRow->additional_data, true))->toBe(['common' => ['quantity' => '3']]);

    // (b) A second, ordinary LEGACY flat-key update (no additional_data) must
    // NOT wipe out the quantity previously written for A via the rich path.
    $this->productRepository->update([
        'sku'      => $source->sku,
        'up_sells' => [$a->sku],
    ], $source->id);

    $upSellsRowAfterLegacyUpdate = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $upSellsType->id)
        ->first();

    expect(json_decode($upSellsRowAfterLegacyUpdate->additional_data, true))->toBe(['common' => ['quantity' => '2']]);
});

it('aborts the save with a validation exception when a link field value is invalid, persisting nothing', function () {
    $bundleKitTypeId = createBundleKitAssociationType();
    $bundleKitType = $this->associationTypeRepository->find($bundleKitTypeId);

    $source = Product::factory()->create();
    $b = Product::factory()->create();

    expect(fn () => $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $b->sku,
                    'additional_data' => ['common' => ['quantity' => 'abc']],
                ],
            ],
        ],
    ], $source->id))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('product_associations', 0);
});

it('allows re-saving a product unchanged when a link sets an `is_unique` field -- the link\'s own persisted value must not fail against itself (Important 1)', function () {
    $type = $this->associationTypeRepository->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [
            [
                'code'        => 'serial_number',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => 1,
                'is_unique'   => 1,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Serial Number'],
            ],
        ],
    ]);

    $source = Product::factory()->create();
    $related = Product::factory()->create();

    $payload = [
        'sku'          => $source->sku,
        'associations' => [
            $type->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['serial_number' => '12345']],
                ],
            ],
        ],
    ];

    // First save persists the link with its `is_unique` field value.
    $this->productRepository->update($payload, $source->id);

    // Re-saving the SAME product with the SAME, unchanged link value must
    // NOT throw: before the fix, `AssociationTypeField::getValidationRules()`
    // emitted a DB-level `unique:product_associations,additional_data->...`
    // rule with no `ignore` id (validation runs before save, so no id is
    // known yet), so the link's own already-persisted row matched itself and
    // aborted every re-save. This call is the assertion -- a thrown
    // `ValidationException` fails the test.
    $this->productRepository->update($payload, $source->id);

    $row = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $type->id)
        ->first();

    expect($row)->not->toBeNull();
    expect(json_decode($row->additional_data, true))->toBe(['common' => ['serial_number' => '12345']]);
});

it('prunes all `product_associations` rows for a CUSTOM type when it is present in the payload but has zero links (simulates removing the last link in the UI)', function () {
    $bundleKitTypeId = createBundleKitAssociationType();
    $bundleKitType = $this->associationTypeRepository->find($bundleKitTypeId);

    $source = Product::factory()->create();
    $related = Product::factory()->create();

    $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['quantity' => '4']],
                ],
            ],
        ],
    ], $source->id);

    $this->assertDatabaseCount('product_associations', 1);

    // Removing the last link in the UI still submits the type's key --
    // `links.blade.php`'s `__present` sentinel -- with no numeric link rows,
    // exactly as native form submission would produce once every link of a
    // type is removed.
    $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            $bundleKitType->code => ['__present' => '1'],
        ],
    ], $source->id);

    $this->assertDatabaseCount('product_associations', 0);
});

it('prunes rows AND clears the legacy JSON list for a LEGACY section (up_sells) present-but-empty in the payload', function () {
    $upSellsType = $this->associationTypeRepository->findByCode('up_sells');

    $source = Product::factory()->create();
    $related = Product::factory()->create();

    $updated = $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            'up_sells' => [
                ['sku' => $related->sku],
            ],
        ],
    ], $source->id);

    expect($updated->values['associations']['up_sells'] ?? null)->toBe([$related->sku]);

    $this->assertDatabaseCount('product_associations', 1);

    $updated = $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            'up_sells' => ['__present' => '1'],
        ],
    ], $source->id);

    expect($updated->values['associations']['up_sells'] ?? null)->toBe([]);

    $row = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $upSellsType->id)
        ->first();

    expect($row)->toBeNull();
});

it('does NOT prune existing association links on an update with no `associations` key at all -- the REST/import back-compat path', function () {
    $bundleKitTypeId = createBundleKitAssociationType();
    $bundleKitType = $this->associationTypeRepository->find($bundleKitTypeId);

    $source = Product::factory()->create();
    $related = Product::factory()->create();

    $this->productRepository->update([
        'sku'          => $source->sku,
        'associations' => [
            $bundleKitType->code => [
                [
                    'sku'             => $related->sku,
                    'additional_data' => ['common' => ['quantity' => '4']],
                ],
            ],
        ],
    ], $source->id);

    $this->assertDatabaseCount('product_associations', 1);

    // The REST/import write path never sends an `associations` key at all
    // (see `AbstractType::update()`): this must keep relying on the legacy
    // `! empty($data[section])` fallback UNCHANGED and must NOT prune the
    // rich link created above.
    $this->productRepository->update([
        'sku' => $source->sku,
    ], $source->id);

    $row = DB::table('product_associations')
        ->where('product_id', $source->id)
        ->where('association_type_id', $bundleKitType->id)
        ->first();

    expect($row)->not->toBeNull();
    expect(json_decode($row->additional_data, true))->toBe(['common' => ['quantity' => '4']]);
});
