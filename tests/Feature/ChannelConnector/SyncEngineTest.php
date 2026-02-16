<?php

use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Services\SyncEngine;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->syncEngine = app(SyncEngine::class);
});

it('builds payload with locales and common keys', function () {
    $localeValues = [
        'en' => ['name' => 'English Product', 'description' => 'English desc'],
        'ar' => ['name' => 'منتج عربي', 'description' => 'وصف عربي'],
    ];

    $commonValues = ['sku' => 'TEST-001', 'price' => 99.99];

    $payload = $this->syncEngine->buildPayload($localeValues, $commonValues);

    expect($payload)->toHaveKeys(['locales', 'common']);
    expect($payload['locales'])->toHaveKeys(['en', 'ar']);
    expect($payload['locales']['en']['name'])->toBe('English Product');
    expect($payload['locales']['ar']['name'])->toBe('منتج عربي');
    expect($payload['common']['sku'])->toBe('TEST-001');
    expect($payload['common']['price'])->toBe(99.99);
});

it('builds payload with empty locales', function () {
    $payload = $this->syncEngine->buildPayload([], ['sku' => 'COMMON-ONLY']);

    expect($payload['locales'])->toBeEmpty();
    expect($payload['common']['sku'])->toBe('COMMON-ONLY');
});

it('computes deterministic data hash', function () {
    $payload = ['common' => ['sku' => 'HASH-TEST'], 'locales' => ['en' => ['name' => 'Test']]];

    $hash1 = $this->syncEngine->computeDataHash($payload);
    $hash2 = $this->syncEngine->computeDataHash($payload);

    expect($hash1)->toBe($hash2);
    expect($hash1)->not->toBeEmpty();
});

it('produces different hashes for different payloads', function () {
    $payload1 = ['common' => ['sku' => 'A'], 'locales' => []];
    $payload2 = ['common' => ['sku' => 'B'], 'locales' => []];

    $hash1 = $this->syncEngine->computeDataHash($payload1);
    $hash2 = $this->syncEngine->computeDataHash($payload2);

    expect($hash1)->not->toBe($hash2);
});

it('computeHash is alias for computeDataHash', function () {
    $payload = ['common' => ['sku' => 'ALIAS-TEST'], 'locales' => ['en' => ['name' => 'Alias']]];

    $hash1 = $this->syncEngine->computeDataHash($payload);
    $hash2 = $this->syncEngine->computeHash($payload);

    expect($hash1)->toBe($hash2);
});

it('hash ignores key order', function () {
    $payload1 = ['common' => ['sku' => 'A', 'price' => 10], 'locales' => []];
    $payload2 = ['locales' => [], 'common' => ['price' => 10, 'sku' => 'A']];

    $hash1 = $this->syncEngine->computeDataHash($payload1);
    $hash2 = $this->syncEngine->computeDataHash($payload2);

    expect($hash1)->toBe($hash2);
});

it('detects changes when no existing mapping', function () {
    $product = Product::factory()->create();
    $mappings = collect([]);

    $changed = $this->syncEngine->detectChanges($product, null, $mappings);

    expect($changed)->toBeTrue();
});

it('detects changes when mapping has no hash', function () {
    $product = Product::factory()->create();

    $connector = ChannelConnector::create([
        'code'         => 'hash-test',
        'name'         => 'Hash Test',
        'channel_type' => 'salla',
        'credentials'  => ['access_token' => 'test'],
        'status'       => 'connected',
    ]);

    $mapping = ProductChannelMapping::create([
        'channel_connector_id' => $connector->id,
        'product_id'           => $product->id,
        'external_id'          => 'ext-1',
        'entity_type'          => 'product',
        'data_hash'            => null,
    ]);

    $changed = $this->syncEngine->detectChanges($product, $mapping, collect([]));

    expect($changed)->toBeTrue();
});
