<?php

use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

/*
 * The product sku search dropped a redundant COALESCE self-join that multiplied
 * every row then collapsed it with group by — pure scan overhead on a large
 * catalog. Search results must be unchanged and the join gone.
 */
it('builds the search query without a variant self-join', function () {
    [$query] = app(ProductRepository::class)->queryBuilderFromDatabase(['sku' => 'x']);

    $sql = strtolower($query->toSql());

    expect($sql)->not->toContain('coalesce')
        ->and($sql)->not->toContain('as `variants`')
        ->and($sql)->not->toContain('group by');
});

it('still finds a product by sku substring', function () {
    $product = Product::factory()->simple()->create(['sku' => 'FINDME-ABC']);

    request()->merge(['query' => 'FINDME']);

    expect(app(ProductRepository::class)->searchFromDatabase()->pluck('sku')->all())
        ->toContain('FINDME-ABC');
});
