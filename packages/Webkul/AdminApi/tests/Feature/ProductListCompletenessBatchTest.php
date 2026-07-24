<?php

use Illuminate\Support\Facades\DB;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Product\Models\Product;

/*
 * The product list API resolved completeness per row (one query per product).
 * With ?with_completeness=1 the scores for the whole page must be batched.
 */
it('batches completeness scores for the product list', function () {
    $headers = $this->getAuthenticationHeaders();

    foreach (['CMP-A', 'CMP-B', 'CMP-C'] as $sku) {
        $product = Product::factory()->simple()->create(['sku' => $sku]);

        ProductCompletenessScore::create([
            'product_id' => $product->id,
            'channel_id' => 1,
            'locale_id'  => 1,
            'score'      => 60,
        ]);
    }

    $completenessQueries = 0;

    DB::listen(function ($query) use (&$completenessQueries): void {
        if (str_contains($query->sql, 'product_completeness')) {
            $completenessQueries++;
        }
    });

    $this->withHeaders($headers)
        ->json('GET', route('admin.api.products.index', ['with_completeness' => 1, 'limit' => 10]))
        ->assertOk();

    expect($completenessQueries)->toBeLessThanOrEqual(1);
});
