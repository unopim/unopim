<?php

use Webkul\ChannelConnector\Services\SyncEngine;

it('builds locale-keyed payload structure', function () {
    $engine = app(SyncEngine::class);

    // Test payload construction helper
    $payload = $engine->buildPayload(
        localeValues: ['en' => ['title' => 'Product'], 'ar' => ['title' => 'منتج']],
        commonValues: ['sku' => 'TEST-001', 'price' => 99.99]
    );

    expect($payload)->toHaveKey('locales');
    expect($payload)->toHaveKey('common');
    expect($payload['locales']['en']['title'])->toBe('Product');
    expect($payload['locales']['ar']['title'])->toBe('منتج');
    expect($payload['common']['sku'])->toBe('TEST-001');
});

it('computes deterministic data hash across locales', function () {
    $engine = app(SyncEngine::class);

    $payload1 = ['locales' => ['en' => ['title' => 'A'], 'ar' => ['title' => 'ب']], 'common' => ['sku' => '1']];
    $payload2 = ['locales' => ['ar' => ['title' => 'ب'], 'en' => ['title' => 'A']], 'common' => ['sku' => '1']];

    expect($engine->computeDataHash($payload1))->toBe($engine->computeDataHash($payload2));
});
