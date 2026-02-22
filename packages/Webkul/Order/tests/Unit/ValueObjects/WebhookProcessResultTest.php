<?php

use Webkul\Order\ValueObjects\WebhookProcessResult;

it('can create successful webhook process result', function () {
    $result = new WebhookProcessResult(
        status: 'success',
        message: 'Order created successfully',
        orderId: 123
    );

    expect($result->status)->toBe('success')
        ->and($result->message)->toBe('Order created successfully')
        ->and($result->orderId)->toBe(123);
});

it('can create failed webhook process result', function () {
    $result = new WebhookProcessResult(
        status: 'error',
        message: 'Invalid signature',
        error: 'HMAC verification failed'
    );

    expect($result->status)->toBe('error')
        ->and($result->error)->toBe('HMAC verification failed');
});

it('determines if processing was successful', function () {
    $success = new WebhookProcessResult(
        status: 'success',
        message: 'Processed',
        orderId: 123
    );

    $failed = new WebhookProcessResult(
        status: 'error',
        message: 'Failed',
        error: 'Invalid data'
    );

    expect($success->isSuccessful())->toBeTrue()
        ->and($failed->isSuccessful())->toBeFalse();
});

it('converts to array correctly', function () {
    $result = new WebhookProcessResult(
        status: 'success',
        message: 'Order updated',
        orderId: 456
    );

    $array = $result->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys(['status', 'message'])
        ->and($array['status'])->toBe('success')
        ->and($array['order_id'])->toBe(456);
});

it('includes error in array when present', function () {
    $result = new WebhookProcessResult(
        status: 'error',
        message: 'Processing failed',
        error: 'Database connection error'
    );

    $array = $result->toArray();

    expect($array)->toHaveKey('error')
        ->and($array['error'])->toBe('Database connection error');
});

it('creates success result using factory method', function () {
    $result = WebhookProcessResult::success(
        message: 'Order created',
        orderId: 789
    );

    expect($result->status)->toBe('success')
        ->and($result->orderId)->toBe(789);
});

it('creates error result using factory method', function () {
    $result = WebhookProcessResult::error(
        message: 'Validation failed',
        error: 'Missing required fields'
    );

    expect($result->status)->toBe('error')
        ->and($result->error)->toBe('Missing required fields');
});
