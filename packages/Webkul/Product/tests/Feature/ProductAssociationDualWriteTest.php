<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->productRepository = app(ProductRepository::class);
});

it('dual-writes associations to the link table on product update, mirroring the legacy JSON', function () {
    $source = Product::factory()->create();

    $upSellA = Product::factory()->create();
    $upSellB = Product::factory()->create();
    $related = Product::factory()->create();

    $updated = $this->productRepository->update([
        'sku'              => $source->sku,
        'up_sells'         => [$upSellA->sku, $upSellB->sku],
        'related_products' => [$related->sku],
    ], $source->id);

    // (a) legacy JSON is written exactly as before.
    expect($updated->values['associations']['up_sells'] ?? null)->toBe([$upSellA->sku, $upSellB->sku])
        ->and($updated->values['associations']['related_products'] ?? null)->toBe([$related->sku]);

    // (b) the link table mirrors it.
    $this->assertDatabaseCount('product_associations', 3);

    expect(
        DB::table('product_associations')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->where('product_associations.product_id', $source->id)
            ->where('association_types.code', 'up_sells')
            ->count()
    )->toBe(2)
        ->and(
            DB::table('product_associations')
                ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
                ->where('product_associations.product_id', $source->id)
                ->where('association_types.code', 'related_products')
                ->count()
        )->toBe(1);

    // Now shrink up_sells to a single SKU; the removal must propagate to the table.
    $reUpdated = $this->productRepository->update([
        'sku'      => $source->sku,
        'up_sells' => [$upSellA->sku],
    ], $source->id);

    expect($reUpdated->values['associations']['up_sells'] ?? null)->toBe([$upSellA->sku]);

    $this->assertDatabaseCount('product_associations', 2);

    expect(
        DB::table('product_associations')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->where('product_associations.product_id', $source->id)
            ->where('association_types.code', 'up_sells')
            ->count()
    )->toBe(1);
});
