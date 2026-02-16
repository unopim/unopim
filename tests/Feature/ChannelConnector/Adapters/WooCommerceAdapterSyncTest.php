<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Product\Models\Product;
use Webkul\WooCommerce\Adapters\WooCommerceAdapter;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new WooCommerceAdapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
    $this->adapter->setConnectorId(1);
});

it('creates a product on WooCommerce', function () {
    Http::fake([
        'api.woocommerce.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 456, 'name' => 'Test WooCommerce Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test WooCommerce Product', 'price' => '49.99', 'sku' => 'WOO-001', 'status' => 'active'],
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
        'api.woocommerce.dev/admin/v2/products/456' => Http::response([
            'data' => [
                'name'        => 'Fetched WooCommerce Product',
                'description' => 'A test product description',
                'price'       => ['amount' => 49.99],
                'sku'         => 'WOO-001',
                'quantity'    => 100,
                'weight'      => 0.5,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('456');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched WooCommerce Product');
    expect($result['common']['price'])->toBe(49.99);
    expect($result['common']['sku'])->toBe('WOO-001');
    expect($result['common']['status'])->toBe('sale');
});

it('deletes a product from WooCommerce', function () {
    Http::fake([
        'api.woocommerce.dev/admin/v2/products/456' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('456');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.woocommerce.dev/admin/v2/products' => Http::response([
            'error' => ['message' => 'Invalid product data'],
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

// ─── Connection Tests ──────────────────────────────────────────────────

it('tests connection to WooCommerce API', function () {
    Http::fake([
        'api.woocommerce.dev/admin/v2/products*' => Http::response([
            'data'       => [['store' => ['name' => 'My WooCommerce Store']]],
            'pagination' => ['total' => 42],
        ], 200),
    ]);

    $result = $this->adapter->testConnection(['access_token' => 'valid_token']);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Connection verified successfully.');
    expect($result->channelInfo['store_name'])->toBe('My WooCommerce Store');
    expect($result->channelInfo['product_count'])->toBe(42);
});

it('fails connection with missing access token', function () {
    $result = $this->adapter->testConnection([]);

    expect($result->success)->toBeFalse();
    expect($result->message)->toBe('Access token is required.');
    expect($result->errors)->toContain('Missing access token');
});

// ─── Webhook Verification Tests ────────────────────────────────────────

it('verifies webhook with valid HMAC signature', function () {
    $adapter = new WooCommerceAdapter;
    $adapter->setCredentials(['webhook_secret' => 'test_secret']);

    $payload = '{"event":"product.updated"}';
    $signature = hash_hmac('sha256', $payload, 'test_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_WOOCOMMERCE_SIGNATURE' => $signature,
    ], $payload);

    expect($adapter->verifyWebhook($request))->toBeTrue();
});

it('rejects webhook with invalid signature', function () {
    $adapter = new WooCommerceAdapter;
    $adapter->setCredentials(['webhook_secret' => 'test_secret']);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_WOOCOMMERCE_SIGNATURE' => 'invalid_signature',
    ], '{"event":"product.updated"}');

    expect($adapter->verifyWebhook($request))->toBeFalse();
});

// ─── Credential Refresh Tests ──────────────────────────────────────────

it('refreshes OAuth credentials successfully', function () {
    Http::fake([
        'accounts.woocommerce.sa/oauth2/token' => Http::response([
            'access_token'  => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in'    => 3600,
        ], 200),
    ]);

    $adapter = new WooCommerceAdapter;
    $adapter->setCredentials([
        'refresh_token' => 'old_refresh_token',
        'client_id'     => 'test_client',
        'client_secret' => 'test_secret',
    ]);

    $result = $adapter->refreshCredentials();

    expect($result)->not->toBeNull();
    expect($result['access_token'])->toBe('new_access_token');
    expect($result['refresh_token'])->toBe('new_refresh_token');
    expect($result)->toHaveKey('expires_at');
});

it('returns null when refresh token is missing', function () {
    $adapter = new WooCommerceAdapter;
    $adapter->setCredentials([]);

    expect($adapter->refreshCredentials())->toBeNull();
});

// ─── Body Build & Normalize Tests ──────────────────────────────────────

it('builds correct WooCommerce product body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '149.99', 'sku' => 'WOO-AR', 'status' => 'active', 'quantity' => 10, 'weight' => '2.5'],
        'locales' => [
            'ar' => ['name' => 'منتج ووكومرس', 'description' => 'وصف المنتج'],
            'en' => ['name' => 'WooCommerce Product', 'description' => 'Product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildWooCommerceProductBody');
    $body = $method->invoke($this->adapter, $payload);

    expect($body['name'])->toBe('منتج ووكومرس');
    expect($body['description'])->toBe('وصف المنتج');
    expect($body['price']['amount'])->toBe(149.99);
    expect($body['sku'])->toBe('WOO-AR');
    expect($body['status'])->toBe('sale');
    expect($body['quantity'])->toBe(10);
    expect($body['weight'])->toBe(2.5);
});

it('normalizes WooCommerce product data correctly', function () {
    $data = [
        'name'        => 'Normalized Product',
        'description' => 'Product description',
        'price'       => ['amount' => 49.99],
        'sale_price'  => ['amount' => 39.99],
        'sku'         => 'WOO-NORM',
        'quantity'    => 25,
        'weight'      => 1.5,
        'status'      => 'sale',
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeWooCommerceProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['name'])->toBe('Normalized Product');
    expect($normalized['common']['price'])->toBe(49.99);
    expect($normalized['common']['sale_price'])->toBe(39.99);
    expect($normalized['common']['sku'])->toBe('WOO-NORM');
    expect($normalized['common']['quantity'])->toBe(25);
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
