<?php

use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductAssociationRepository;

it('resolves skus, skips self and unresolved skus, and prunes stale links on re-sync', function () {
    $repo = app(ProductAssociationRepository::class);

    $source = Product::factory()->create();
    $targetA = Product::factory()->create();
    $targetB = Product::factory()->create();

    $repo->syncFromSkuList($source->id, 'up_sells', [
        $targetA->sku,
        $targetB->sku,
        $source->sku,
        'NONEXISTENT',
    ]);

    $links = $repo->getLinksForProduct($source->id);

    expect($links)->toHaveCount(2)
        ->and($links->pluck('related_product_id')->sort()->values()->all())
        ->toBe(collect([$targetA->id, $targetB->id])->sort()->values()->all())
        ->and($links->first()->relationLoaded('relatedProduct'))->toBeTrue()
        ->and($links->first()->relationLoaded('associationType'))->toBeTrue()
        ->and($links->first()->associationType->code)->toBe('up_sells');

    // Re-sync with a single SKU should prune the stale link.
    $repo->syncFromSkuList($source->id, 'up_sells', [$targetA->sku]);

    $prunedLinks = $repo->getLinksForProduct($source->id);

    expect($prunedLinks)->toHaveCount(1)
        ->and($prunedLinks->first()->related_product_id)->toBe($targetA->id);
});
