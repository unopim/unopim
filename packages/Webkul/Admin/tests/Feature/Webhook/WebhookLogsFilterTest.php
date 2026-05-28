<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->loginAsAdmin();

    DB::table('webhook_logs')->delete();

    DB::table('webhook_logs')->insert([
        [
            'sku'        => 'SUCCESS-200',
            'user'       => 'tester',
            'status'     => 1,
            'extra'      => json_encode(['response' => ['status' => 200]]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'SUCCESS-201',
            'user'       => 'tester',
            'status'     => 1,
            'extra'      => json_encode(['response' => ['status' => 201]]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'FAILED-404',
            'user'       => 'tester',
            'status'     => 0,
            'extra'      => json_encode(['response' => ['status' => 404]]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'SERVER-500',
            'user'       => 'tester',
            'status'     => 0,
            'extra'      => json_encode(['response' => ['status' => 500]]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'TIMEOUT-NULL',
            'user'       => 'tester',
            'status'     => 0,
            'extra'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'TIMEOUT-ZERO',
            'user'       => 'tester',
            'status'     => 0,
            'extra'      => json_encode(['response' => ['status' => 0, 'error' => 'cURL error']]),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
});

afterEach(function () {
    DB::table('webhook_logs')->where('user', 'tester')->delete();
});

it('builds status dropdown options from the actual response codes present in webhook_logs', function () {
    $response = $this->getJson(
        route('webhook.logs.index'),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $statusColumn = collect($response->json('columns'))
        ->firstWhere('index', 'status');

    expect($statusColumn)->not->toBeNull();
    expect($statusColumn['type'])->toBe('dropdown');
    expect($statusColumn['options']['type'])->toBe('basic');

    $options = collect($statusColumn['options']['params']['options']);

    expect($options->pluck('value')->sort()->values()->all())->toBe([
        '0:404',
        '0:500',
        '1:200',
        '1:201',
        'timeout_or_error',
    ]);

    $labelsByValue = $options->pluck('label', 'value')->all();
    expect($labelsByValue['1:200'])->toBe('Success (200)');
    expect($labelsByValue['1:201'])->toBe('Success (201)');
    expect($labelsByValue['0:404'])->toBe('Failed (404)');
    expect($labelsByValue['0:500'])->toBe('Server Error (500)');
    expect($labelsByValue['timeout_or_error'])->toBe('Timeout/Error');
});

it('returns only the matching row for a specific success code', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['1:200']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $skus = collect($response->json('records'))
        ->where('user', 'tester')
        ->pluck('sku')
        ->values()
        ->all();

    expect($skus)->toBe(['SUCCESS-200']);
});

it('returns only the matching row for a specific 4xx code', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['0:404']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $skus = collect($response->json('records'))
        ->where('user', 'tester')
        ->pluck('sku')
        ->values()
        ->all();

    expect($skus)->toBe(['FAILED-404']);
});

it('returns only the matching row for a specific 5xx code', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['0:500']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $skus = collect($response->json('records'))
        ->where('user', 'tester')
        ->pluck('sku')
        ->values()
        ->all();

    expect($skus)->toBe(['SERVER-500']);
});

it('returns null-code and zero-code rows for the timeout_or_error filter', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['timeout_or_error']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $skus = collect($response->json('records'))
        ->where('user', 'tester')
        ->pluck('sku')
        ->sort()
        ->values()
        ->all();

    expect($skus)->toBe(['TIMEOUT-NULL', 'TIMEOUT-ZERO']);
});

it('combines multiple selected codes with OR semantics', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['0:404', '0:500']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $skus = collect($response->json('records'))
        ->where('user', 'tester')
        ->pluck('sku')
        ->sort()
        ->values()
        ->all();

    expect($skus)->toBe(['FAILED-404', 'SERVER-500']);
});
