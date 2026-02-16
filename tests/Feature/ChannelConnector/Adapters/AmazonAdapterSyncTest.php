<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Webkul\Amazon\Adapters\AmazonAdapter;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new AmazonAdapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
    $this->adapter->setConnectorId(1);
});

it('creates a new product on Amazon via syncProduct', function () {
    Http::fake([
        'api.amazon.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 55001, 'name' => 'Test Amazon Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Amazon Product', 'price' => '29.99', 'sku' => 'AMZ-001', 'status' => 'active'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('55001');
    expect($result->action)->toBe('created');
});

it('fetches and normalizes a product from Amazon', function () {
    Http::fake([
        'api.amazon.dev/admin/v2/products/55001' => Http::response([
            'data' => [
                'id'          => 55001,
                'name'        => 'Fetched Amazon Product',
                'description' => 'A test Amazon product',
                'price'       => ['amount' => 29.99, 'currency' => 'SAR'],
                'sku'         => 'AMZ-001',
                'quantity'    => 100,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('55001');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Amazon Product');
    expect($result['common']['price'])->toBe(29.99);
    expect($result['common']['sku'])->toBe('AMZ-001');
    expect($result['common']['status'])->toBe('sale');
});

it('returns null for non-existent Amazon product', function () {
    Http::fake([
        'api.amazon.dev/admin/v2/products/99999' => Http::response(null, 404),
    ]);

    $result = $this->adapter->fetchProduct('99999');

    expect($result)->toBeNull();
});

it('deletes a product from Amazon', function () {
    Http::fake([
        'api.amazon.dev/admin/v2/products/55001' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('55001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.amazon.dev/admin/v2/products' => Http::response([
            'error' => ['message' => 'Invalid product data'],
        ], 400),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Bad Product', 'sku' => 'BAD-001'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
});

it('builds correct Amazon product body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '39.99', 'sku' => 'AMZ-002', 'status' => 'active'],
        'locales' => [
            'ar' => ['name' => 'منتج أمازون', 'description' => 'وصف أمازون'],
            'en' => ['name' => 'Amazon Product', 'description' => 'Amazon description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildAmazonProductBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic should be preferred
    expect($body['name'])->toBe('منتج أمازون');
    expect($body['description'])->toBe('وصف أمازون');
    expect($body['price']['amount'])->toBe(39.99);
    expect($body['price']['currency'])->toBe('SAR');
    expect($body['sku'])->toBe('AMZ-002');
    expect($body['status'])->toBe('sale');
});

it('normalizes Amazon product data correctly', function () {
    $data = [
        'name'        => 'Normalized Amazon Product',
        'description' => 'Product description',
        'price'       => ['amount' => 49.99],
        'sale_price'  => ['amount' => 39.99],
        'sku'         => 'AMZ-NORM',
        'quantity'    => 200,
        'weight'      => 1.5,
        'status'      => 'sale',
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeAmazonProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['name'])->toBe('Normalized Amazon Product');
    expect($normalized['common']['price'])->toBe(49.99);
    expect($normalized['common']['sale_price'])->toBe(39.99);
    expect($normalized['common']['sku'])->toBe('AMZ-NORM');
    expect($normalized['locales'])->toBeEmpty();
});

it('returns correct channel fields', function () {
    $fields = $this->adapter->getChannelFields();

    $codes = array_column($fields, 'code');
    expect($codes)->toContain('name');
    expect($codes)->toContain('description');
    expect($codes)->toContain('price');
    expect($codes)->toContain('sku');
    expect($codes)->toContain('status');
    expect($fields)->toHaveCount(9);
});

it('returns correct supported locales and currencies', function () {
    $locales = $this->adapter->getSupportedLocales();
    expect($locales)->toContain('ar');
    expect($locales)->toContain('en');

    $currencies = $this->adapter->getSupportedCurrencies();
    expect($currencies)->toContain('SAR');
    expect($currencies)->toContain('USD');
    expect($currencies)->toContain('EUR');
});

// ─── Connection Tests ──────────────────────────────────────────────────

it('tests connection to Amazon API', function () {
    Http::fake([
        'api.amazon.dev/admin/v2/products*' => Http::response([
            'data'       => [['store' => ['name' => 'Amazon Seller Store']]],
            'pagination' => ['total' => 500],
        ], 200),
    ]);

    $result = $this->adapter->testConnection(['access_token' => 'valid_amazon_token']);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Connection verified successfully.');
    expect($result->channelInfo['store_name'])->toBe('Amazon Seller Store');
});

it('fails connection with missing access token', function () {
    $result = $this->adapter->testConnection([]);

    expect($result->success)->toBeFalse();
    expect($result->errors)->toContain('Missing access token');
});

// ─── Webhook Verification Tests ────────────────────────────────────────

it('verifies webhook with valid HMAC signature', function () {
    $adapter = new AmazonAdapter;
    $adapter->setCredentials(['webhook_secret' => 'amz_webhook_secret']);

    $payload = '{"event":"product.updated"}';
    $signature = hash_hmac('sha256', $payload, 'amz_webhook_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_AMAZON_SIGNATURE' => $signature,
    ], $payload);

    expect($adapter->verifyWebhook($request))->toBeTrue();
});

it('rejects webhook with invalid signature', function () {
    $adapter = new AmazonAdapter;
    $adapter->setCredentials(['webhook_secret' => 'amz_webhook_secret']);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_AMAZON_SIGNATURE' => 'wrong_signature',
    ], '{"event":"product.updated"}');

    expect($adapter->verifyWebhook($request))->toBeFalse();
});

// ─── Credential Refresh Tests ──────────────────────────────────────────

it('refreshes OAuth credentials successfully', function () {
    Http::fake([
        'accounts.amazon.sa/oauth2/token' => Http::response([
            'access_token'  => 'new_amz_token',
            'refresh_token' => 'new_amz_refresh',
            'expires_in'    => 3600,
        ], 200),
    ]);

    $adapter = new AmazonAdapter;
    $adapter->setCredentials([
        'refresh_token' => 'old_refresh',
        'client_id'     => 'amz_client',
        'client_secret' => 'amz_secret',
    ]);

    $result = $adapter->refreshCredentials();

    expect($result)->not->toBeNull();
    expect($result['access_token'])->toBe('new_amz_token');
    expect($result['refresh_token'])->toBe('new_amz_refresh');
    expect($result)->toHaveKey('expires_at');
});

it('returns null when refresh token is missing', function () {
    $adapter = new AmazonAdapter;
    $adapter->setCredentials([]);

    expect($adapter->refreshCredentials())->toBeNull();
});
