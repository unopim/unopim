<?php

use Illuminate\Support\Facades\Http;
use Webkul\EasyOrders\Adapters\EasyOrdersAdapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new EasyOrdersAdapter;
    $this->adapter->setCredentials([
        'api_key'  => 'test_api_key',
        'api_base' => 'https://api.easyorders.test/v1',
    ]);
});

it('tests connection to EasyOrders API', function () {
    Http::fake([
        'api.easyorders.test/v1/products*' => Http::response([
            'store_name' => 'My EasyOrders Store',
            'total'      => 42,
        ], 200),
    ]);

    $result = $this->adapter->testConnection([
        'api_key'  => 'test_api_key',
        'api_base' => 'https://api.easyorders.test/v1',
    ]);

    expect($result->success)->toBeTrue();
    expect($result->channelInfo['store_name'])->toBe('My EasyOrders Store');
});

it('creates a product on EasyOrders', function () {
    Http::fake([
        'api.easyorders.test/v1/products' => Http::response([
            'data' => ['id' => 789],
        ], 201),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Product', 'price' => '49.99', 'sku' => 'EO-001', 'commission_rate' => '10'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('789');
    expect($result->action)->toBe('created');
});

it('fetches a product from EasyOrders', function () {
    Http::fake([
        'api.easyorders.test/v1/products/789' => Http::response([
            'data' => [
                'id'                => 789,
                'name'              => 'Fetched Product',
                'price'             => 49.99,
                'sku'               => 'EO-001',
                'commission_rate'   => 10,
                'commission_amount' => 4.99,
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('789');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Product');
    expect($result['common']['commission_rate'])->toBe(10);
});

it('deletes a product from EasyOrders', function () {
    Http::fake([
        'api.easyorders.test/v1/products/789' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('789');

    expect($result)->toBeTrue();
});

it('returns failure when API credentials are missing for sync', function () {
    $adapter = new EasyOrdersAdapter;
    $adapter->setCredentials([]);

    $product = Product::factory()->create();
    $payload = ['common' => ['name' => 'Test'], 'locales' => []];

    $result = $adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->errors)->not->toBeEmpty();
});

it('builds correct EasyOrders body with commission fields', function () {
    $payload = [
        'common'  => [
            'name'              => 'Commission Product',
            'price'             => '99.99',
            'sku'               => 'COMM-001',
            'commission_rate'   => '15',
            'commission_amount' => '14.99',
        ],
        'locales' => ['ar' => ['name' => 'منتج بعمولة']],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildEasyOrdersBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic preferred
    expect($body['name'])->toBe('منتج بعمولة');
    expect($body['price'])->toBe(99.99);
    expect($body['commission_rate'])->toBe(15.0);
    expect($body['commission_amount'])->toBe(14.99);
});
