<?php

use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Noon\Adapters\NoonAdapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new NoonAdapter;
    $this->adapter->setCredentials([
        'api_key'    => 'test_noon_key',
        'api_secret' => 'test_noon_secret',
        'api_base'   => 'https://api.noon.test/v1',
    ]);
});

it('creates a product on Noon', function () {
    Http::fake([
        'api.noon.test/v1/products' => Http::response([
            'data' => ['id' => 9001],
        ], 201),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Noon Product', 'price' => '149.99', 'partner_sku' => 'NOON-001'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('9001');
    expect($result->action)->toBe('created');
});

it('fetches a product from Noon', function () {
    Http::fake([
        'api.noon.test/v1/products/9001' => Http::response([
            'data' => [
                'id'            => 9001,
                'name'          => 'Fetched Noon Product',
                'description'   => 'A product on Noon',
                'price'         => 149.99,
                'partner_sku'   => 'NOON-001',
                'barcode'       => '6281234567890',
                'brand'         => 'TestBrand',
                'category_path' => 'Electronics > Gadgets',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('9001');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Noon Product');
    expect($result['common']['price'])->toBe(149.99);
    expect($result['common']['partner_sku'])->toBe('NOON-001');
    expect($result['common']['barcode'])->toBe('6281234567890');
    expect($result['common']['brand'])->toBe('TestBrand');
});

it('deletes a product from Noon', function () {
    Http::fake([
        'api.noon.test/v1/products/9001' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('9001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.noon.test/v1/products' => Http::response([
            'message' => 'Validation error: partner_sku is required',
        ], 422),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Bad Noon Product'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
});

it('builds correct Noon body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '199.99', 'partner_sku' => 'AR-NOON-001', 'barcode' => '6281234567890'],
        'locales' => [
            'ar' => ['name' => 'منتج نون', 'description' => 'وصف منتج نون'],
            'en' => ['name' => 'Noon Product', 'description' => 'Noon product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildNoonBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic should be preferred
    expect($body['name'])->toBe('منتج نون');
    expect($body['description'])->toBe('وصف منتج نون');
    expect($body['price'])->toBe(199.99);
    expect($body['partner_sku'])->toBe('AR-NOON-001');
    expect($body['barcode'])->toBe('6281234567890');
});
