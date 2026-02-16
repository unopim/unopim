<?php

use Webkul\ChannelConnector\Services\TransformationEngine;

it('applies markup_percentage correctly', function () {
    $result = TransformationEngine::apply(100, [
        ['type' => 'markup_percentage', 'config' => ['percentage' => 10]],
    ]);

    expect($result)->toBe(110.0);
});

it('applies markup_fixed correctly', function () {
    $result = TransformationEngine::apply(100, [
        ['type' => 'markup_fixed', 'config' => ['amount' => 5]],
    ]);

    expect($result)->toBe(105.0);
});

it('applies round_price with round strategy', function () {
    $result = TransformationEngine::apply(142.356, [
        ['type' => 'round_price', 'config' => ['precision' => 2, 'strategy' => 'round']],
    ]);

    expect($result)->toBe(142.36);
});

it('applies round_price with ceil strategy', function () {
    $result = TransformationEngine::apply(142.351, [
        ['type' => 'round_price', 'config' => ['precision' => 2, 'strategy' => 'ceil']],
    ]);

    expect($result)->toBe(142.36);
});

it('applies round_price with floor strategy', function () {
    $result = TransformationEngine::apply(142.359, [
        ['type' => 'round_price', 'config' => ['precision' => 2, 'strategy' => 'floor']],
    ]);

    expect($result)->toBe(142.35);
});

it('applies round_price with round_99 strategy', function () {
    $result = TransformationEngine::apply(142.35, [
        ['type' => 'round_price', 'config' => ['strategy' => 'round_99']],
    ]);

    expect($result)->toBe(142.99);
});

it('applies pricing pipeline', function () {
    $result = TransformationEngine::apply(100, [
        ['type' => 'markup_percentage', 'config' => ['percentage' => 10]],
        ['type' => 'round_price', 'config' => ['precision' => 2, 'strategy' => 'round']],
    ]);

    expect($result)->toBe(110.0);
});
