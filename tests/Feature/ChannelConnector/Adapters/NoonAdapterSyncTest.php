<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Noon\Adapters\NoonAdapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new NoonAdapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
    $this->adapter->setConnectorId(1);
});

it('creates a product on Noon', function () {
    Http::fake([
        'api.noon.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 9001, 'name' => 'Test Noon Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Noon Product', 'price' => '149.99', 'sku' => 'NOON-001', 'status' => 'active'],
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
        'api.noon.dev/admin/v2/products/9001' => Http::response([
            'data' => [
                'id'          => 9001,
                'name'        => 'Fetched Noon Product',
                'description' => 'A product on Noon',
                'price'       => ['amount' => 149.99, 'currency' => 'SAR'],
                'sku'         => 'NOON-001',
                'quantity'    => 50,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('9001');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Noon Product');
    expect($result['common']['price'])->toBe(149.99);
    expect($result['common']['sku'])->toBe('NOON-001');
    expect($result['common']['status'])->toBe('sale');
});

it('deletes a product from Noon', function () {
    Http::fake([
        'api.noon.dev/admin/v2/products/9001' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('9001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.noon.dev/admin/v2/products' => Http::response([
            'error' => ['message' => 'Validation error: sku is required'],
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
        'common'  => ['price' => '199.99', 'sku' => 'AR-NOON-001'],
        'locales' => [
            'ar' => ['name' => 'منتج نون', 'description' => 'وصف منتج نون'],
            'en' => ['name' => 'Noon Product', 'description' => 'Noon product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildNoonProductBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic should be preferred
    expect($body['name'])->toBe('منتج نون');
    expect($body['description'])->toBe('وصف منتج نون');
    expect($body['price']['amount'])->toBe(199.99);
    expect($body['price']['currency'])->toBe('SAR');
    expect($body['sku'])->toBe('AR-NOON-001');
});

// ─── Connection Tests ──────────────────────────────────────────────────

it('tests connection to Noon API', function () {
    Http::fake([
        'api.noon.dev/admin/v2/products*' => Http::response([
            'data'       => [['store' => ['name' => 'My Noon Store']]],
            'pagination' => ['total' => 150],
        ], 200),
    ]);

    $result = $this->adapter->testConnection(['access_token' => 'valid_noon_token']);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Connection verified successfully.');
    expect($result->channelInfo['store_name'])->toBe('My Noon Store');
    expect($result->channelInfo['product_count'])->toBe(150);
});

it('fails connection with missing access token', function () {
    $result = $this->adapter->testConnection([]);

    expect($result->success)->toBeFalse();
    expect($result->message)->toBe('Access token is required.');
    expect($result->errors)->toContain('Missing access token');
});

// ─── Webhook Verification Tests ────────────────────────────────────────

it('verifies webhook with valid HMAC signature', function () {
    $adapter = new NoonAdapter;
    $adapter->setCredentials(['webhook_secret' => 'noon_secret']);

    $payload = '{"event":"product.updated"}';
    $signature = hash_hmac('sha256', $payload, 'noon_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_NOON_SIGNATURE' => $signature,
    ], $payload);

    expect($adapter->verifyWebhook($request))->toBeTrue();
});

it('rejects webhook with invalid signature', function () {
    $adapter = new NoonAdapter;
    $adapter->setCredentials(['webhook_secret' => 'noon_secret']);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_NOON_SIGNATURE' => 'invalid_signature',
    ], '{"event":"product.updated"}');

    expect($adapter->verifyWebhook($request))->toBeFalse();
});

// ─── Credential Refresh Tests ──────────────────────────────────────────

it('refreshes OAuth credentials successfully', function () {
    Http::fake([
        'accounts.noon.sa/oauth2/token' => Http::response([
            'access_token'  => 'new_noon_token',
            'refresh_token' => 'new_noon_refresh',
            'expires_in'    => 3600,
        ], 200),
    ]);

    $adapter = new NoonAdapter;
    $adapter->setCredentials([
        'refresh_token' => 'old_refresh',
        'client_id'     => 'noon_client',
        'client_secret' => 'noon_secret',
    ]);

    $result = $adapter->refreshCredentials();

    expect($result)->not->toBeNull();
    expect($result['access_token'])->toBe('new_noon_token');
    expect($result['refresh_token'])->toBe('new_noon_refresh');
    expect($result)->toHaveKey('expires_at');
});

it('returns null when refresh token is missing', function () {
    $adapter = new NoonAdapter;
    $adapter->setCredentials([]);

    expect($adapter->refreshCredentials())->toBeNull();
});

// ─── Normalize & Channel Info Tests ────────────────────────────────────

it('normalizes Noon product data correctly', function () {
    $data = [
        'name'       => 'Normalized Noon Product',
        'description' => 'Noon description',
        'price'      => ['amount' => 89.99],
        'sale_price' => ['amount' => 69.99],
        'sku'        => 'NOON-NORM',
        'quantity'   => 30,
        'weight'     => 0.8,
        'status'     => 'sale',
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeNoonProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['name'])->toBe('Normalized Noon Product');
    expect($normalized['common']['price'])->toBe(89.99);
    expect($normalized['common']['sale_price'])->toBe(69.99);
    expect($normalized['common']['sku'])->toBe('NOON-NORM');
    expect($normalized['locales'])->toBeEmpty();
});

it('returns correct channel fields and supported locales', function () {
    $fields = $this->adapter->getChannelFields();
    $codes = array_column($fields, 'code');
    expect($codes)->toContain('name');
    expect($codes)->toContain('price');
    expect($codes)->toContain('sku');
    expect($fields)->toHaveCount(9);

    $locales = $this->adapter->getSupportedLocales();
    expect($locales)->toContain('ar');
    expect($locales)->toContain('en');

    $currencies = $this->adapter->getSupportedCurrencies();
    expect($currencies)->toContain('SAR');
    expect($currencies)->toContain('USD');
});
