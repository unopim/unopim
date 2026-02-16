<?php

use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Ebay\Adapters\EbayAdapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new EbayAdapter;
    $this->adapter->setCredentials([
        'access_token'   => 'v^1.1#test_ebay_token',
        'marketplace_id' => 'EBAY_US',
        'client_id'      => 'test_client_id',
        'client_secret'  => 'test_client_secret',
        'refresh_token'  => 'test_refresh_token',
    ]);
});

it('creates a product on eBay via Inventory API', function () {
    Http::fake([
        'api.ebay.com/sell/inventory/v1/inventory_item/*' => Http::response(null, 204),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['title' => 'Test eBay Product', 'price' => '49.99', 'sku' => 'EBAY-001', 'condition' => 'NEW', 'quantity' => 10],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('EBAY-001');
    expect($result->action)->toBe('created');
});

it('fetches a product from eBay', function () {
    Http::fake([
        'api.ebay.com/sell/inventory/v1/inventory_item/*' => Http::response([
            'sku'          => 'EBAY-001',
            'product'      => [
                'title'       => 'Fetched eBay Product',
                'description' => 'A test product',
                'aspects'     => [
                    'Brand' => ['TestBrand'],
                    'MPN'   => ['MPN-001'],
                ],
            ],
            'condition'    => 'NEW',
            'availability' => [
                'shipToLocationAvailability' => ['quantity' => 25],
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('EBAY-001');

    expect($result)->not->toBeNull();
    expect($result['common']['title'])->toBe('Fetched eBay Product');
    expect($result['common']['description'])->toBe('A test product');
    expect($result['common']['condition'])->toBe('NEW');
    expect($result['common']['quantity'])->toBe(25);
    expect($result['common']['sku'])->toBe('EBAY-001');
    expect($result['common']['brand'])->toBe('TestBrand');
    expect($result['common']['mpn'])->toBe('MPN-001');
});

it('deletes a product from eBay', function () {
    Http::fake([
        'api.ebay.com/sell/inventory/v1/inventory_item/*' => Http::response(null, 204),
    ]);

    $result = $this->adapter->deleteProduct('EBAY-001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.ebay.com/sell/inventory/v1/inventory_item/*' => Http::response([
            'errors' => [['message' => 'Invalid inventory item', 'errorId' => 25001]],
        ], 400),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['title' => 'Bad Product', 'sku' => 'BAD-EBAY'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
    expect($result->errors)->not->toBeEmpty();
});

it('fails sync when access token is missing', function () {
    $adapter = new EbayAdapter;
    $adapter->setCredentials([]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['title' => 'Product', 'sku' => 'SKU-001'],
        'locales' => [],
    ];

    $result = $adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
    expect($result->errors[0])->toContain('access_token');
});

it('builds correct eBay body with locale data', function () {
    $payload = [
        'common'  => ['price' => '59.99', 'condition' => 'USED_EXCELLENT', 'quantity' => 5, 'brand' => 'ACME', 'mpn' => 'MPN-002'],
        'locales' => [
            'en_US' => ['title' => 'US eBay Product', 'description' => 'US product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildEbayBody');
    $body = $method->invoke($this->adapter, $payload);

    expect($body['product']['title'])->toBe('US eBay Product');
    expect($body['product']['description'])->toBe('US product description');
    expect($body['condition'])->toBe('USED_EXCELLENT');
    expect($body['availability']['shipToLocationAvailability']['quantity'])->toBe(5);
    expect($body['product']['aspects']['Brand'])->toBe(['ACME']);
    expect($body['product']['aspects']['MPN'])->toBe(['MPN-002']);
});

it('normalizes eBay product data correctly', function () {
    $data = [
        'sku'          => 'NORM-EBAY',
        'product'      => [
            'title'       => 'Normalized eBay Product',
            'description' => 'Normalized description',
            'aspects'     => [
                'Brand' => ['NormBrand'],
                'MPN'   => ['NormMPN'],
            ],
        ],
        'condition'    => 'NEW',
        'availability' => [
            'shipToLocationAvailability' => ['quantity' => 100],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeEbayProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['title'])->toBe('Normalized eBay Product');
    expect($normalized['common']['description'])->toBe('Normalized description');
    expect($normalized['common']['condition'])->toBe('NEW');
    expect($normalized['common']['quantity'])->toBe(100);
    expect($normalized['common']['sku'])->toBe('NORM-EBAY');
    expect($normalized['common']['brand'])->toBe('NormBrand');
    expect($normalized['common']['mpn'])->toBe('NormMPN');
});

it('returns correct channel fields', function () {
    $fields = $this->adapter->getChannelFields();

    $codes = array_column($fields, 'code');
    expect($codes)->toContain('title');
    expect($codes)->toContain('description');
    expect($codes)->toContain('price');
    expect($codes)->toContain('sku');
    expect($codes)->toContain('brand');
    expect($codes)->toContain('mpn');
    expect($codes)->toContain('condition');
});

it('returns correct supported locales and currencies', function () {
    $locales = $this->adapter->getSupportedLocales();
    expect($locales)->toContain('en_US');
    expect($locales)->toContain('en_GB');
    expect($locales)->toContain('de_DE');

    $currencies = $this->adapter->getSupportedCurrencies();
    expect($currencies)->toContain('USD');
    expect($currencies)->toContain('GBP');
    expect($currencies)->toContain('EUR');
});

it('resolves correct content language for different marketplaces', function () {
    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('getContentLanguage');

    // Default EBAY_US from credentials
    expect($method->invoke($this->adapter))->toBe('en-US');

    // Test with different marketplace
    $adapter = new EbayAdapter;
    $adapter->setCredentials(['access_token' => 'test', 'marketplace_id' => 'EBAY_DE']);
    expect($method->invoke($adapter))->toBe('de-DE');

    $adapter2 = new EbayAdapter;
    $adapter2->setCredentials(['access_token' => 'test', 'marketplace_id' => 'EBAY_FR']);
    expect($method->invoke($adapter2))->toBe('fr-FR');
});

it('registerWebhooks returns true (subscription model)', function () {
    $result = $this->adapter->registerWebhooks(['ITEM_SOLD'], 'https://example.com/webhook');

    expect($result)->toBeTrue();
});
