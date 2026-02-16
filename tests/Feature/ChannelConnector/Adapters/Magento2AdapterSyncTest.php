<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Magento2\Adapters\Magento2Adapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new Magento2Adapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
    $this->adapter->setConnectorId(1);
});

it('creates a product on Magento 2', function () {
    Http::fake([
        'api.magento2.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 101, 'name' => 'Test Magento Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Magento Product', 'price' => '59.99', 'sku' => 'MAG-001', 'status' => 'active'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('101');
    expect($result->action)->toBe('created');
});

it('fetches a product from Magento 2', function () {
    Http::fake([
        'api.magento2.dev/admin/v2/products/101' => Http::response([
            'data' => [
                'id'          => 101,
                'name'        => 'Fetched Magento Product',
                'description' => 'Magento product description',
                'price'       => ['amount' => 59.99, 'currency' => 'SAR'],
                'sku'         => 'MAG-001',
                'quantity'    => 50,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('101');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Magento Product');
    expect($result['common']['sku'])->toBe('MAG-001');
    expect($result['common']['price'])->toBe(59.99);
    expect($result['common']['status'])->toBe('sale');
});

it('deletes a product from Magento 2', function () {
    Http::fake([
        'api.magento2.dev/admin/v2/products/101' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('101');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.magento2.dev/admin/v2/products' => Http::response([
            'error' => ['message' => 'The value of attribute "sku" must be unique.'],
        ], 422),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Duplicate Product', 'sku' => 'MAG-DUP'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
});

// ─── Connection Tests ──────────────────────────────────────────────────

it('tests connection to Magento 2 API', function () {
    Http::fake([
        'api.magento2.dev/admin/v2/products*' => Http::response([
            'data'       => [['store' => ['name' => 'My Magento Store']]],
            'pagination' => ['total' => 85],
        ], 200),
    ]);

    $result = $this->adapter->testConnection(['access_token' => 'valid_token']);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Connection verified successfully.');
    expect($result->channelInfo['store_name'])->toBe('My Magento Store');
    expect($result->channelInfo['product_count'])->toBe(85);
});

it('fails connection with missing access token', function () {
    $result = $this->adapter->testConnection([]);

    expect($result->success)->toBeFalse();
    expect($result->message)->toBe('Access token is required.');
    expect($result->errors)->toContain('Missing access token');
});

// ─── Webhook Verification Tests ────────────────────────────────────────

it('verifies webhook with valid HMAC signature', function () {
    $adapter = new Magento2Adapter;
    $adapter->setCredentials(['webhook_secret' => 'mag_secret']);

    $payload = '{"event":"product.updated"}';
    $signature = hash_hmac('sha256', $payload, 'mag_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_MAGENTO2_SIGNATURE' => $signature,
    ], $payload);

    expect($adapter->verifyWebhook($request))->toBeTrue();
});

it('rejects webhook with invalid signature', function () {
    $adapter = new Magento2Adapter;
    $adapter->setCredentials(['webhook_secret' => 'mag_secret']);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_MAGENTO2_SIGNATURE' => 'invalid_signature',
    ], '{"event":"product.updated"}');

    expect($adapter->verifyWebhook($request))->toBeFalse();
});

// ─── Credential Refresh Tests ──────────────────────────────────────────

it('refreshes OAuth credentials successfully', function () {
    Http::fake([
        'accounts.magento2.sa/oauth2/token' => Http::response([
            'access_token'  => 'new_mag_token',
            'refresh_token' => 'new_mag_refresh',
            'expires_in'    => 3600,
        ], 200),
    ]);

    $adapter = new Magento2Adapter;
    $adapter->setCredentials([
        'refresh_token' => 'old_refresh_token',
        'client_id'     => 'mag_client',
        'client_secret' => 'mag_secret',
    ]);

    $result = $adapter->refreshCredentials();

    expect($result)->not->toBeNull();
    expect($result['access_token'])->toBe('new_mag_token');
    expect($result['refresh_token'])->toBe('new_mag_refresh');
    expect($result)->toHaveKey('expires_at');
});

it('returns null when refresh token is missing', function () {
    $adapter = new Magento2Adapter;
    $adapter->setCredentials([]);

    expect($adapter->refreshCredentials())->toBeNull();
});

// ─── Body Build & Normalize Tests ──────────────────────────────────────

it('builds correct Magento 2 product body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '299.99', 'sku' => 'MAG-AR', 'status' => 'active', 'quantity' => 5, 'weight' => '1.2'],
        'locales' => [
            'ar' => ['name' => 'منتج ماجنتو', 'description' => 'وصف المنتج'],
            'en' => ['name' => 'Magento Product', 'description' => 'Product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildMagento2ProductBody');
    $body = $method->invoke($this->adapter, $payload);

    expect($body['name'])->toBe('منتج ماجنتو');
    expect($body['description'])->toBe('وصف المنتج');
    expect($body['price']['amount'])->toBe(299.99);
    expect($body['sku'])->toBe('MAG-AR');
    expect($body['status'])->toBe('sale');
    expect($body['quantity'])->toBe(5);
    expect($body['weight'])->toBe(1.2);
});

it('normalizes Magento 2 product data correctly', function () {
    $data = [
        'name'        => 'Normalized Magento Product',
        'description' => 'Magento description',
        'price'       => ['amount' => 79.99],
        'sale_price'  => ['amount' => 59.99],
        'sku'         => 'MAG-NORM',
        'quantity'    => 50,
        'weight'      => 2.0,
        'status'      => 'sale',
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeMagento2Product');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['name'])->toBe('Normalized Magento Product');
    expect($normalized['common']['price'])->toBe(79.99);
    expect($normalized['common']['sale_price'])->toBe(59.99);
    expect($normalized['common']['sku'])->toBe('MAG-NORM');
    expect($normalized['common']['quantity'])->toBe(50);
    expect($normalized['locales'])->toBeEmpty();
});

// ─── Channel Info Tests ────────────────────────────────────────────────

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
