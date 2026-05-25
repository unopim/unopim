<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->loginAsAdmin();

    DB::table('webhook_logs')->delete();

    DB::table('webhook_logs')->insert([
        [
            'sku'        => 'SUCCESS-001',
            'user'       => 'tester',
            'status'     => 1,
            'extra'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'SUCCESS-002',
            'user'       => 'tester',
            'status'     => 1,
            'extra'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'sku'        => 'FAILED-001',
            'user'       => 'tester',
            'status'     => 0,
            'extra'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
});

afterEach(function () {
    DB::table('webhook_logs')->where('user', 'tester')->delete();
});

it('exposes the status column as a basic dropdown filter with Success/Failed options', function () {
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

    $optionValues = collect($statusColumn['options']['params']['options'])
        ->pluck('value')
        ->map(fn ($v) => (int) $v)
        ->sort()
        ->values()
        ->all();

    expect($optionValues)->toBe([0, 1]);
});

it('returns only the success rows when the status filter value is 1', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['1']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $records = collect($response->json('records'))
        ->where('user', 'tester')
        ->values();

    expect($records)->toHaveCount(2);
    expect($records->pluck('sku')->sort()->values()->all())
        ->toBe(['SUCCESS-001', 'SUCCESS-002']);
});

it('returns only the failed row when the status filter value is 0', function () {
    $response = $this->getJson(
        route('webhook.logs.index', ['filters' => ['status' => ['0']]]),
        ['X-Requested-With' => 'XMLHttpRequest']
    );

    $response->assertOk();

    $records = collect($response->json('records'))
        ->where('user', 'tester')
        ->values();

    expect($records)->toHaveCount(1);
    expect($records[0]['sku'])->toBe('FAILED-001');
});
