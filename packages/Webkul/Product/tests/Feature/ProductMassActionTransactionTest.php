<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

it('rolls back the whole chunk when a status update fails part way through', function () {
    $products = Product::factory()->simple()->count(3)->create(['status' => 0]);

    Event::listen('catalog.product.update.after', function ($product) use ($products): void {
        if ($product->id === $products[1]->id) {
            throw new RuntimeException('Listener failed.');
        }
    });

    expect(fn () => app(ProductRepository::class)->massUpdateStatus($products->pluck('id')->all(), true))
        ->toThrow(RuntimeException::class, 'Listener failed.');

    expect(Product::whereIn('id', $products->pluck('id'))->pluck('status')->map(fn ($status) => (int) $status)->unique()->all())->toBe([0]);
});

it('rolls back the whole chunk and keeps product media when a mass delete fails part way through', function () {
    Storage::fake();

    $products = Product::factory()->simple()->count(3)->create();

    Storage::put('product/'.$products[0]->id.'/image.jpg', 'image');

    Event::listen('catalog.product.delete.after', function ($productId) use ($products): void {
        if ($productId === $products[1]->id) {
            throw new RuntimeException('Listener failed.');
        }
    });

    expect(fn () => app(ProductRepository::class)->massDelete($products->pluck('id')->all()))
        ->toThrow(RuntimeException::class, 'Listener failed.');

    expect(Product::whereIn('id', $products->pluck('id'))->count())->toBe(3);

    Storage::assertExists('product/'.$products[0]->id.'/image.jpg');
});

it('removes product media once a mass delete commits', function () {
    Storage::fake();

    $product = Product::factory()->simple()->create();

    Storage::put('product/'.$product->id.'/image.jpg', 'image');

    app(ProductRepository::class)->massDelete([$product->id]);

    expect(Product::whereKey($product->id)->exists())->toBeFalse();

    Storage::assertMissing('product/'.$product->id.'/image.jpg');
});
