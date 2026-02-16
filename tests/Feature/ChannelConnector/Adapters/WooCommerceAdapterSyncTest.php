<?php

use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;
use Webkul\WooCommerce\Adapters\WooCommerceAdapter;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new WooCommerceAdapter;
    $this->adapter->setCredentials([
        'store_url'       => 'https://woo.test',
        'consumer_key'    => 'ck_test_key',
        'consumer_secret' => 'cs_test_secret',
    ]);
});

it('creates a product on WooCommerce', function () {
    Http::fake([
        'woo.test/wp-json/wc/v3/products' => Http::response([
            'id'   => 456,
            'name' => 'Test WooCommerce Product',
        ], 201),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test WooCommerce Product', 'regular_price' => '49.99', 'sku' => 'WOO-001', 'status' => 'active'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('456');
    expect($result->action)->toBe('created');
});

it('fetches a product from WooCommerce', function () {
    Http::fake([
        'woo.test/wp-json/wc/v3/products/456' => Http::response([
            'id'                => 456,
            'name'              => 'Fetched WooCommerce Product',
            'description'       => 'A test product description',
            'short_description' => 'Short desc',
            'regular_price'     => '49.99',
            'sale_price'        => '39.99',
            'sku'               => 'WOO-001',
            'manage_stock'      => true,
            'stock_quantity'    => 100,
            'weight'            => '0.5',
            'status'            => 'publish',
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('456');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched WooCommerce Product');
    expect($result['common']['regular_price'])->toBe('49.99');
    expect($result['common']['sku'])->toBe('WOO-001');
    expect($result['common']['status'])->toBe('active');
});

it('deletes a product from WooCommerce', function () {
    Http::fake([
        'woo.test/wp-json/wc/v3/products/456*' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('456');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'woo.test/wp-json/wc/v3/products' => Http::response([
            'message' => 'Invalid product data',
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
