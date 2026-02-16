<?php

use Webkul\ChannelConnector\Services\SyncEngine;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('builds payload with correct structure', function () {
    $syncEngine = app(SyncEngine::class);

    $localeValues = ['en' => ['title' => 'English Name']];
    $commonValues = ['sku' => 'TEST-001'];

    $payload = $syncEngine->buildPayload($localeValues, $commonValues);

    expect($payload)->toHaveKey('common');
    expect($payload)->toHaveKey('locales');
    expect($payload['common'])->toBe($commonValues);
    expect($payload['locales'])->toBe($localeValues);
});

it('generates deterministic data hash regardless of key order', function () {
    $syncEngine = app(SyncEngine::class);

    $values1 = ['common' => ['sku' => 'A'], 'locale_specific' => ['en_US' => ['name' => 'B']]];
    $values2 = ['locale_specific' => ['en_US' => ['name' => 'B']], 'common' => ['sku' => 'A']];

    $hash1 = $syncEngine->computeHash($values1);
    $hash2 = $syncEngine->computeHash($values2);

    expect($hash1)->toBe($hash2);
});

it('detects changes when product values differ from stored hash', function () {
    $syncEngine = app(SyncEngine::class);

    $original = ['common' => ['sku' => 'A', 'name' => 'Original']];
    $modified = ['common' => ['sku' => 'A', 'name' => 'Modified']];

    $hash1 = $syncEngine->computeHash($original);
    $hash2 = $syncEngine->computeHash($modified);

    expect($hash1)->not->toBe($hash2);
});
