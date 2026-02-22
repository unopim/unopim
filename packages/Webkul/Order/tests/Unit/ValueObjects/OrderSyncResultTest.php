<?php

use Webkul\Order\ValueObjects\OrderSyncResult;

it('can create sync result with success status', function () {
    $result = new OrderSyncResult(
        status: 'success',
        syncedCount: 10,
        createdCount: 5,
        updatedCount: 5,
        failedCount: 0
    );

    expect($result->status)->toBe('success')
        ->and($result->syncedCount)->toBe(10)
        ->and($result->createdCount)->toBe(5)
        ->and($result->updatedCount)->toBe(5)
        ->and($result->failedCount)->toBe(0);
});

it('can create sync result with failure status', function () {
    $result = new OrderSyncResult(
        status: 'failed',
        syncedCount: 0,
        createdCount: 0,
        updatedCount: 0,
        failedCount: 10,
        error: 'Connection timeout'
    );

    expect($result->status)->toBe('failed')
        ->and($result->failedCount)->toBe(10)
        ->and($result->error)->toBe('Connection timeout');
});

it('calculates total count correctly', function () {
    $result = new OrderSyncResult(
        status: 'success',
        syncedCount: 15,
        createdCount: 8,
        updatedCount: 7,
        failedCount: 0
    );

    expect($result->getTotalCount())->toBe(15);
});

it('determines if sync was successful', function () {
    $success = new OrderSyncResult(
        status: 'success',
        syncedCount: 10,
        createdCount: 5,
        updatedCount: 5,
        failedCount: 0
    );

    $failed = new OrderSyncResult(
        status: 'failed',
        syncedCount: 0,
        createdCount: 0,
        updatedCount: 0,
        failedCount: 5
    );

    expect($success->isSuccessful())->toBeTrue()
        ->and($failed->isSuccessful())->toBeFalse();
});

it('converts to array correctly', function () {
    $result = new OrderSyncResult(
        status: 'success',
        syncedCount: 10,
        createdCount: 5,
        updatedCount: 5,
        failedCount: 0
    );

    $array = $result->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys(['status', 'synced_count', 'created_count', 'updated_count', 'failed_count'])
        ->and($array['status'])->toBe('success')
        ->and($array['synced_count'])->toBe(10);
});

it('converts to JSON correctly', function () {
    $result = new OrderSyncResult(
        status: 'success',
        syncedCount: 10,
        createdCount: 5,
        updatedCount: 5,
        failedCount: 0
    );

    $json = $result->toJson();

    expect($json)->toBeJson()
        ->and(json_decode($json, true))->toHaveKey('status', 'success');
});

it('includes error message when present', function () {
    $result = new OrderSyncResult(
        status: 'failed',
        syncedCount: 0,
        createdCount: 0,
        updatedCount: 0,
        failedCount: 5,
        error: 'API rate limit exceeded'
    );

    $array = $result->toArray();

    expect($array)->toHaveKey('error')
        ->and($array['error'])->toBe('API rate limit exceeded');
});
