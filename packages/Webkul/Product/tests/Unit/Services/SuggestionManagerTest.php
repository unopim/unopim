<?php

use Webkul\Product\Services\SuggestionManager;
use Webkul\Product\Services\VariantPlacementSuggester;

it('resolves a registered suggester by key', function () {
    $manager = app(SuggestionManager::class);

    expect($manager->resolve('variant_placement'))->toBeInstanceOf(VariantPlacementSuggester::class)
        ->and($manager->resolve('does_not_exist'))->toBeNull();
});

it('falls back to rules when AI is not allowed', function () {
    $manager = app(SuggestionManager::class);

    $context = [
        'attributes' => [
            ['code' => 'price', 'type' => 'price', 'is_unique' => false],
            ['code' => 'brand', 'type' => 'select', 'is_unique' => false],
        ],
        'levels'    => 2,
        'axisCodes' => [],
    ];

    // AI toggle is off by default -> canUseAi is false -> rules run, no AI call.
    expect($manager->canUseAi('variant_placement'))->toBeFalse();

    $result = $manager->suggest('variant_placement', $context, true);

    expect($result)->toMatchArray([
        'price' => 'variant',
        'brand' => 'common',
    ]);
});

it('returns empty for an unknown suggester', function () {
    expect(app(SuggestionManager::class)->suggest('nope', [], false))->toBe([]);
});
