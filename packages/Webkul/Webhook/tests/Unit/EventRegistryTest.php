<?php

use Webkul\Webhook\Registry\EventRegistry;

it('seeds groups from the constructor', function () {
    $registry = new EventRegistry([
        'product' => ['product.created' => 'lang.created'],
    ]);

    expect($registry->keys())->toBe(['product.created']);
    expect($registry->has('product.created'))->toBeTrue();
    expect($registry->has('order.created'))->toBeFalse();
});

it('lets third-party packages register and extend events', function () {
    $registry = new EventRegistry([
        'product' => ['product.created' => 'lang.created'],
    ]);

    $registry->register('order', ['order.created' => 'shop.order.created']);
    $registry->register('product', ['product.deleted' => 'lang.deleted']);

    expect($registry->keys())->toContain('product.created', 'product.deleted', 'order.created');
    expect(array_keys($registry->groups()))->toBe(['product', 'order']);
});

it('exposes the container-bound registry with the built-in product events', function () {
    $registry = app(EventRegistry::class);

    expect($registry->has('product.created'))->toBeTrue();
    expect($registry->has('product.updated'))->toBeTrue();
});
