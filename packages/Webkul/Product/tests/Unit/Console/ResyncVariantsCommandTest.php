<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Product\Models\Product;

uses(DatabaseTransactions::class);

it('queues a completeness resync for the whole variant subtree', function () {
    Queue::fake();

    $parent = Product::factory()->configurable()->create([
        'values' => ['common' => ['brand' => 'Nike']],
    ]);

    Product::factory()->count(2)->create(['parent_id' => $parent->id]);

    $this->artisan('unopim:variants:resync', ['--all' => true])->assertSuccessful();

    Queue::assertPushed(ProductCompletenessJob::class);
});

it('fails when neither --product nor --all is given', function () {
    $this->artisan('unopim:variants:resync')->assertFailed();
});
