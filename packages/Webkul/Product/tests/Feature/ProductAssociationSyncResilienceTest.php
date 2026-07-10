<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductAssociationRepository;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Task 6 / review follow-up (Task 4 resilience): `AbstractType::syncAssociationLinks()`
 * wraps each section's `syncFromSkuList()` call in its own try/catch and
 * reports (rather than rethrows) any failure, so a broken link-table sync
 * must never abort — or roll back — the product save that already happened.
 *
 * No production code changes for this test: the try/catch already exists
 * (added in Task 4). This is a regression guard proving that resilience
 * holds, by forcing `syncFromSkuList` to throw for every association
 * section.
 */
beforeEach(function () {
    $this->productRepository = app(ProductRepository::class);
});

it('does not abort the product save when association link-table sync throws', function () {
    app()->instance(ProductAssociationRepository::class, new class extends ProductAssociationRepository
    {
        public function __construct() {}

        public function syncFromSkuList(int $productId, string $typeCode, array $skus): void
        {
            throw new RuntimeException('simulated product_associations sync failure');
        }
    });

    $source = Product::factory()->withInitialValues()->create();

    $related = Product::factory()->create();
    $upSell = Product::factory()->create();

    $updated = $this->productRepository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
        'up_sells'         => [$upSell->sku],
    ], $source->id);

    // The legacy JSON write (the product save) still succeeded, despite the
    // link-table sync throwing for every section.
    expect($updated->values['associations']['related_products'] ?? null)->toBe([$related->sku])
        ->and($updated->values['associations']['up_sells'] ?? null)->toBe([$upSell->sku]);

    $this->assertDatabaseHas('products', [
        'id' => $source->id,
    ]);

    // No exception propagated out of ->update(), and no rows made it into
    // the (broken) link table.
    $this->assertDatabaseCount('product_associations', 0);
});
