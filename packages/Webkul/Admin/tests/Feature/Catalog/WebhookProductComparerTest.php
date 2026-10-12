<?php

use Webkul\Webhook\Helpers\ProductComparer;

it('detects status transitions from 0 to 1', function () {
    $diff = ProductComparer::compare(['status' => 0], ['status' => 1]);

    expect($diff['changed']['status'])->toBe(['old' => 0, 'new' => 1]);
});

it('detects status transitions from 1 to 0', function () {
    $diff = ProductComparer::compare(['status' => 1], ['status' => 0]);

    expect($diff['changed']['status'])->toBe(['old' => 1, 'new' => 0]);
});

it('does not record an unchanged status as a change', function () {
    $diff = ProductComparer::compare(['status' => 1], ['status' => 1]);

    expect($diff['changed'] ?? [])->toBe([]);
    expect($diff['added'] ?? [])->toBe([]);
    expect($diff['removed'] ?? [])->toBe([]);
});

it('returns a structured empty diff when neither side has values or status', function () {
    $diff = ProductComparer::compare([], []);

    expect($diff)->toBe(['added' => [], 'removed' => [], 'changed' => []]);
});

it('captures changed common values alongside status transitions', function () {
    $old = [
        'status' => 0,
        'values' => json_encode(['common' => ['brand' => 'Nike']]),
    ];
    $new = [
        'status' => 1,
        'values' => json_encode(['common' => ['brand' => 'Adidas']]),
    ];

    $diff = ProductComparer::compare($old, $new);

    expect($diff['changed']['status'])->toBe(['old' => 0, 'new' => 1]);
    expect($diff['changed']['common']['brand'])->toBe(['old' => 'Nike', 'new' => 'Adidas']);
});
