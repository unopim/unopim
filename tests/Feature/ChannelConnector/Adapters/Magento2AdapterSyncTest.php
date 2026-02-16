<?php

use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Magento2\Adapters\Magento2Adapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new Magento2Adapter;
    $this->adapter->setCredentials([
        'store_url'    => 'https://magento.test',
        'access_token' => 'test_bearer_token',
    ]);
});

it('creates a product on Magento 2', function () {
    Http::fake([
        'magento.test/rest/V1/products' => Http::response([
            'id'         => 101,
            'sku'        => 'MAG-001',
            'name'       => 'Test Magento Product',
            'price'      => 59.99,
            'status'     => 1,
            'visibility' => 4,
            'type_id'    => 'simple',
        ], 200),
    ]);

    $product = Product::factory()->create(['sku' => 'MAG-001']);
    $payload = [
        'common'  => ['name' => 'Test Magento Product', 'price' => '59.99', 'sku' => 'MAG-001', 'status' => 'active'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('MAG-001');
    expect($result->action)->toBe('created');
});

it('fetches a product from Magento 2', function () {
    Http::fake([
        'magento.test/rest/V1/products/MAG-001' => Http::response([
            'id'                => 101,
            'sku'               => 'MAG-001',
            'name'              => 'Fetched Magento Product',
            'price'             => 59.99,
            'weight'            => 1.5,
            'status'            => 1,
            'custom_attributes' => [
                ['attribute_code' => 'description', 'value' => '<p>Magento product description</p>'],
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('MAG-001');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Magento Product');
    expect($result['common']['sku'])->toBe('MAG-001');
    expect($result['common']['price'])->toBe(59.99);
    expect($result['common']['status'])->toBe('active');
    expect($result['common']['description'])->toBe('<p>Magento product description</p>');
});

it('deletes a product from Magento 2', function () {
    Http::fake([
        'magento.test/rest/V1/products/MAG-001' => Http::response(true, 200),
    ]);

    $result = $this->adapter->deleteProduct('MAG-001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'magento.test/rest/V1/products' => Http::response([
            'message' => 'The value of attribute "sku" must be unique.',
        ], 422),
    ]);

    $product = Product::factory()->create(['sku' => 'MAG-DUP']);
    $payload = [
        'common'  => ['name' => 'Duplicate Product', 'sku' => 'MAG-DUP'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
});
