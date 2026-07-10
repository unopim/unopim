<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Task 6 / Gap 2: `AbstractType::updateWithValues()` persists `values` on the
 * product directly, without going through `AbstractType::update()`. Since it
 * accepts an arbitrary `values` payload (same shape as `update()`, including
 * `values.associations`), any caller that includes associations in that
 * payload would otherwise silently diverge the `product_associations` table
 * from the JSON. This proves the sync now runs for this path too.
 */
beforeEach(function () {
    $this->productRepository = app(ProductRepository::class);
});

it('dual-writes associations to the link table when updated via updateWithValues', function () {
    $source = Product::factory()->withInitialValues()->create();

    $related = Product::factory()->create();
    $upSell = Product::factory()->create();

    $updated = $this->productRepository->updateWithValues([
        'sku'    => $source->sku,
        'values' => [
            'common' => [
                'sku' => $source->sku,
            ],
            'associations' => [
                'related_products' => [$related->sku],
                'up_sells'         => [$upSell->sku],
            ],
        ],
    ], $source->id);

    // Legacy JSON is written verbatim, exactly like before.
    expect($updated->values['associations']['related_products'] ?? null)->toBe([$related->sku])
        ->and($updated->values['associations']['up_sells'] ?? null)->toBe([$upSell->sku]);

    // The link table now mirrors it.
    $this->assertDatabaseCount('product_associations', 2);

    expect(
        DB::table('product_associations')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->where('product_associations.product_id', $source->id)
            ->where('association_types.code', 'related_products')
            ->where('product_associations.related_product_id', $related->id)
            ->exists()
    )->toBeTrue()
        ->and(
            DB::table('product_associations')
                ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
                ->where('product_associations.product_id', $source->id)
                ->where('association_types.code', 'up_sells')
                ->where('product_associations.related_product_id', $upSell->id)
                ->exists()
        )->toBeTrue();
});

it('removes stale link-table rows when associations shrink via updateWithValues', function () {
    $source = Product::factory()->withInitialValues()->create();

    $upSellA = Product::factory()->create();
    $upSellB = Product::factory()->create();

    $this->productRepository->updateWithValues([
        'sku'    => $source->sku,
        'values' => [
            'common'       => ['sku' => $source->sku],
            'associations' => ['up_sells' => [$upSellA->sku, $upSellB->sku]],
        ],
    ], $source->id);

    $this->assertDatabaseCount('product_associations', 2);

    $this->productRepository->updateWithValues([
        'sku'    => $source->sku,
        'values' => [
            'common'       => ['sku' => $source->sku],
            'associations' => ['up_sells' => [$upSellA->sku]],
        ],
    ], $source->id);

    $this->assertDatabaseCount('product_associations', 1);
});
