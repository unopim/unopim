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
