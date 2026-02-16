<?php

use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;
use Webkul\Salla\Adapters\SallaAdapter;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new SallaAdapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
});

it('creates a new product on Salla via syncProduct', function () {
    Http::fake([
        'api.salla.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 12345, 'name' => 'Test Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Product', 'price' => '99.99', 'sku' => 'SALLA-001', 'status' => 'active'],
        'locales' => ['ar' => ['name' => 'منتج تجريبي']],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('12345');
    expect($result->action)->toBe('created');
});

it('fetches and normalizes a product from Salla', function () {
    Http::fake([
        'api.salla.dev/admin/v2/products/12345' => Http::response([
            'data' => [
                'id'          => 12345,
                'name'        => 'Test Product',
                'description' => 'A test product',
                'price'       => ['amount' => 99.99, 'currency' => 'SAR'],
                'sku'         => 'SALLA-001',
                'quantity'    => 50,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('12345');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Test Product');
    expect($result['common']['price'])->toBe(99.99);
    expect($result['common']['sku'])->toBe('SALLA-001');
    expect($result['common']['status'])->toBe('sale');
});

it('returns null for non-existent Salla product', function () {
    Http::fake([
        'api.salla.dev/admin/v2/products/99999' => Http::response(null, 404),
    ]);

    $result = $this->adapter->fetchProduct('99999');

    expect($result)->toBeNull();
});

it('deletes a product from Salla', function () {
    Http::fake([
        'api.salla.dev/admin/v2/products/12345' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('12345');

    expect($result)->toBeTrue();
});

it('registers webhooks on Salla', function () {
    Http::fake([
        'api.salla.dev/admin/v2/webhooks' => Http::response(['data' => ['id' => 1]], 200),
    ]);

    $result = $this->adapter->registerWebhooks(
        ['product.created', 'product.updated'],
        'https://my-app.com/webhook'
    );

    expect($result)->toBeTrue();
    Http::assertSentCount(2);
});

it('builds correct Salla product body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '149.99', 'sku' => 'AR-PROD', 'status' => 'active'],
        'locales' => [
            'ar' => ['name' => 'منتج عربي', 'description' => 'وصف عربي'],
            'en' => ['name' => 'Arabic Product', 'description' => 'Arabic description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildSallaProductBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic should be preferred
    expect($body['name'])->toBe('منتج عربي');
    expect($body['description'])->toBe('وصف عربي');
    expect($body['price']['amount'])->toBe(149.99);
    expect($body['price']['currency'])->toBe('SAR');
    expect($body['status'])->toBe('sale');
});

it('maps status values correctly for Salla', function () {
    $payload = [
        'common'  => ['name' => 'Draft Product', 'status' => 'draft'],
        'locales' => [],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildSallaProductBody');
    $body = $method->invoke($this->adapter, $payload);

    expect($body['status'])->toBe('hidden');
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.salla.dev/admin/v2/products' => Http::response([
            'error' => ['message' => 'Validation failed'],
        ], 422),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Bad Product'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
});
