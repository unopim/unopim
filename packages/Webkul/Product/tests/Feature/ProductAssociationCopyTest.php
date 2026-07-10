<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Task 6 / Gap 3: `AbstractType::copy()` replicates the product row,
 * including its `values['associations']` JSON, but never wrote any
 * `product_associations` rows for the copy — silently diverging the table
 * from the JSON for every copied product. This proves the copy now gets its
 * own matching link-table rows.
 */
beforeEach(function () {
    $this->productRepository = app(ProductRepository::class);
});

it('dual-writes associations to the link table when a product is copied', function () {
    $source = Product::factory()->withInitialValues()->create(['type' => 'simple']);

    $related = Product::factory()->create();
    $upSell = Product::factory()->create();

    $this->productRepository->update([
        'sku'              => $source->sku,
        'related_products' => [$related->sku],
        'up_sells'         => [$upSell->sku],
    ], $source->id);

    $this->assertDatabaseCount('product_associations', 2);

    $copiedProduct = $this->productRepository->copy($source->id);

    // The legacy JSON was already replicated verbatim by `replicate()`.
    expect($copiedProduct->values['associations']['related_products'] ?? null)->toBe([$related->sku])
        ->and($copiedProduct->values['associations']['up_sells'] ?? null)->toBe([$upSell->sku]);

    // The copy must get its own matching product_associations rows.
    $this->assertDatabaseCount('product_associations', 4);

    expect(
        DB::table('product_associations')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->where('product_associations.product_id', $copiedProduct->id)
            ->where('association_types.code', 'related_products')
            ->where('product_associations.related_product_id', $related->id)
            ->exists()
    )->toBeTrue()
        ->and(
            DB::table('product_associations')
                ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
                ->where('product_associations.product_id', $copiedProduct->id)
                ->where('association_types.code', 'up_sells')
                ->where('product_associations.related_product_id', $upSell->id)
                ->exists()
        )->toBeTrue();

    // The original product's own rows are untouched.
    expect(
        DB::table('product_associations')->where('product_id', $source->id)->count()
    )->toBe(2);
});

it('does not create link-table rows when copying a product without associations', function () {
    $source = Product::factory()->withInitialValues()->create(['type' => 'simple']);

    $copiedProduct = $this->productRepository->copy($source->id);

    $this->assertDatabaseCount('product_associations', 0);

    expect($copiedProduct->id)->not->toBe($source->id);
});
