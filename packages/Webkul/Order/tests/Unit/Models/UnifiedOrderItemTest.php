<?php

use Webkul\Order\Models\UnifiedOrder;
use Webkul\Order\Models\UnifiedOrderItem;
use Webkul\Product\Models\Product;

it('can create order item with factory', function () {
    $item = UnifiedOrderItem::factory()->create();

    expect($item)->toBeInstanceOf(UnifiedOrderItem::class)
        ->and($item->id)->not->toBeNull()
        ->and($item->tenant_id)->not->toBeNull();
});

it('belongs to order', function () {
    $order = UnifiedOrder::factory()->create();
    $item = UnifiedOrderItem::factory()->for($order)->create();

    expect($item->order)->toBeInstanceOf(UnifiedOrder::class)
        ->and($item->order->id)->toBe($order->id);
});

it('belongs to product', function () {
    $product = Product::factory()->create();
    $item = UnifiedOrderItem::factory()->create(['product_id' => $product->id]);

    expect($item->product)->toBeInstanceOf(Product::class)
        ->and($item->product->id)->toBe($product->id);
});

it('calculates line total correctly', function () {
    $item = UnifiedOrderItem::factory()->create([
        'price' => 25.50,
        'quantity' => 3,
    ]);

    expect($item->getLineTotal())->toBe(76.50);
});

it('calculates profit correctly', function () {
    $item = UnifiedOrderItem::factory()->create([
        'price' => 100.00,
        'quantity' => 2,
        'cost_basis' => 60.00,
    ]);

    expect($item->getProfit())->toBe(80.00); // (100 - 60) * 2
});

it('calculates margin percentage correctly', function () {
    $item = UnifiedOrderItem::factory()->create([
        'price' => 100.00,
        'quantity' => 1,
        'cost_basis' => 60.00,
    ]);

    expect($item->getMarginPercentage())->toBe(40.00); // ((100 - 60) / 100) * 100
});

it('returns zero margin for zero price', function () {
    $item = UnifiedOrderItem::factory()->create([
        'price' => 0.00,
        'quantity' => 1,
        'cost_basis' => 0.00,
    ]);

    expect($item->getMarginPercentage())->toBe(0.00);
});

it('has fillable attributes', function () {
    $item = new UnifiedOrderItem();

    expect($item->getFillable())->toBeArray()
        ->and($item->getFillable())->toContain('product_id', 'sku', 'name', 'price', 'quantity', 'cost_basis');
});

it('casts attributes correctly', function () {
    $item = UnifiedOrderItem::factory()->create([
        'price' => '99.99',
        'quantity' => '5',
        'cost_basis' => '50.00',
    ]);

    expect($item->price)->toBeFloat()
        ->and($item->quantity)->toBeInt()
        ->and($item->cost_basis)->toBeFloat();
});

it('scopes items by product', function () {
    $product = Product::factory()->create();

    UnifiedOrderItem::factory()->count(3)->create(['product_id' => $product->id]);
    UnifiedOrderItem::factory()->count(2)->create();

    $items = UnifiedOrderItem::byProduct($product->id)->get();

    expect($items)->toHaveCount(3);
});

it('scopes items by SKU', function () {
    UnifiedOrderItem::factory()->count(2)->create(['sku' => 'SKU-001']);
    UnifiedOrderItem::factory()->count(3)->create(['sku' => 'SKU-002']);

    $items = UnifiedOrderItem::bySku('SKU-001')->get();

    expect($items)->toHaveCount(2);
});

it('soft deletes items', function () {
    $item = UnifiedOrderItem::factory()->create();
    $itemId = $item->id;

    $item->delete();

    expect(UnifiedOrderItem::find($itemId))->toBeNull()
        ->and(UnifiedOrderItem::withTrashed()->find($itemId))->not->toBeNull();
});
