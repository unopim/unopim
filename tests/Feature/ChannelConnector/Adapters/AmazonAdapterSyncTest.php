<?php

use Illuminate\Support\Facades\Http;
use Webkul\Amazon\Adapters\AmazonAdapter;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new AmazonAdapter;
    $this->adapter->setCredentials([
        'seller_id'      => 'A1SELLER123',
        'marketplace_id' => 'ATVPDKIKX0DER',
        'region'         => 'us-east-1',
        'client_id'      => 'amzn1.application-oa2-client.test',
        'client_secret'  => 'test_client_secret',
        'refresh_token'  => 'test_refresh_token',
        'secret_key'     => 'test_secret_key',
    ]);
});

it('creates a product on Amazon via SP-API', function () {
    Http::fake([
        'api.amazon.com/auth/o2/token' => Http::response([
            'access_token' => 'test_access_token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
        ], 200),

        'sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/A1SELLER123/*' => Http::response([
            'sku'    => 'AMZ-001',
            'status' => 'ACCEPTED',
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['item_name' => 'Test Amazon Product', 'price' => '29.99', 'sku' => 'AMZ-001', 'brand' => 'TestBrand'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('AMZ-001');
    expect($result->action)->toBe('created');
});

it('fetches a product from Amazon catalog', function () {
    Http::fake([
        'api.amazon.com/auth/o2/token' => Http::response([
            'access_token' => 'test_access_token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
        ], 200),

        'sellingpartnerapi-na.amazon.com/catalog/2022-04-01/items/*' => Http::response([
            'asin'       => 'B00TEST123',
            'attributes' => [
                'item_name'           => [['value' => 'Fetched Amazon Product', 'language_tag' => 'en_US']],
                'product_description' => [['value' => 'A test Amazon product']],
                'brand'               => [['value' => 'TestBrand']],
            ],
            'summaries' => [
                ['itemName' => 'Fetched Amazon Product', 'brand' => 'TestBrand'],
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('B00TEST123');

    expect($result)->not->toBeNull();
    expect($result['common']['item_name'])->toBe('Fetched Amazon Product');
    expect($result['common']['brand'])->toBe('TestBrand');
    expect($result['common']['asin'])->toBe('B00TEST123');
});

it('deletes a product from Amazon', function () {
    Http::fake([
        'api.amazon.com/auth/o2/token' => Http::response([
            'access_token' => 'test_access_token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
        ], 200),

        'sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/A1SELLER123/*' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('AMZ-001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.amazon.com/auth/o2/token' => Http::response([
            'access_token' => 'test_access_token',
            'token_type'   => 'bearer',
            'expires_in'   => 3600,
        ], 200),

        'sellingpartnerapi-na.amazon.com/listings/2021-08-01/items/A1SELLER123/*' => Http::response([
            'errors' => [['message' => 'Invalid product data', 'code' => 'INVALID_INPUT']],
        ], 400),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['item_name' => 'Bad Product', 'sku' => 'BAD-001'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
    expect($result->errors)->not->toBeEmpty();
});

it('fails sync when credentials are missing', function () {
    $adapter = new AmazonAdapter;
    $adapter->setCredentials([]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['item_name' => 'Product', 'sku' => 'SKU-001'],
        'locales' => [],
    ];

    $result = $adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
    expect($result->errors[0])->toContain('seller_id');
});

it('builds correct Amazon body with locale data', function () {
    $payload = [
        'common'  => ['price' => '39.99', 'brand' => 'ACME', 'sku' => 'AMZ-002'],
        'locales' => [
            'en_US' => ['item_name' => 'US Product Name', 'product_description' => 'US Description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildAmazonBody');
    $body = $method->invoke($this->adapter, $payload);

    expect($body['attributes']['item_name'][0]['value'])->toBe('US Product Name');
    expect($body['attributes']['item_name'][0]['language_tag'])->toBe('en_US');
    expect($body['attributes']['product_description'][0]['value'])->toBe('US Description');
    expect($body['attributes']['brand'][0]['value'])->toBe('ACME');
    expect($body['attributes']['purchasable_offer'][0]['our_price'][0]['schedule'][0]['value_with_tax'])->toBe(39.99);
});

it('normalizes Amazon product data correctly', function () {
    $data = [
        'asin'       => 'B00NORM123',
        'attributes' => [
            'item_name'           => [['value' => 'Normalized Product']],
            'product_description' => [['value' => 'Product description']],
            'bullet_point'        => [['value' => 'Feature bullet']],
            'brand'               => [['value' => 'NormBrand']],
            'manufacturer'        => [['value' => 'NormMfg']],
        ],
        'summaries' => [],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeAmazonProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['item_name'])->toBe('Normalized Product');
    expect($normalized['common']['product_description'])->toBe('Product description');
    expect($normalized['common']['bullet_point'])->toBe('Feature bullet');
    expect($normalized['common']['brand'])->toBe('NormBrand');
    expect($normalized['common']['manufacturer'])->toBe('NormMfg');
    expect($normalized['common']['asin'])->toBe('B00NORM123');
});

it('returns correct channel fields', function () {
    $fields = $this->adapter->getChannelFields();

    $codes = array_column($fields, 'code');
    expect($codes)->toContain('item_name');
    expect($codes)->toContain('product_description');
    expect($codes)->toContain('price');
    expect($codes)->toContain('sku');
    expect($codes)->toContain('brand');
});

it('returns correct supported locales and currencies', function () {
    $locales = $this->adapter->getSupportedLocales();
    expect($locales)->toContain('en_US');
    expect($locales)->toContain('ar_AE');
    expect($locales)->toContain('de_DE');

    $currencies = $this->adapter->getSupportedCurrencies();
    expect($currencies)->toContain('USD');
    expect($currencies)->toContain('AED');
    expect($currencies)->toContain('EUR');
});

it('resolves correct API base for different regions', function () {
    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('getApiBase');

    expect($method->invoke($this->adapter, 'us-east-1'))->toBe('https://sellingpartnerapi-na.amazon.com');
    expect($method->invoke($this->adapter, 'eu-west-1'))->toBe('https://sellingpartnerapi-eu.amazon.com');
    expect($method->invoke($this->adapter, 'us-west-2'))->toBe('https://sellingpartnerapi-fe.amazon.com');
    expect($method->invoke($this->adapter, 'unknown-region'))->toBe('https://sellingpartnerapi-na.amazon.com');
});
