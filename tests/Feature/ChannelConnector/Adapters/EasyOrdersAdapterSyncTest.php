<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\EasyOrders\Adapters\EasyOrdersAdapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new EasyOrdersAdapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
    $this->adapter->setConnectorId(1);
});

it('tests connection to EasyOrders API', function () {
    Http::fake([
        'api.easyorders.dev/admin/v2/products*' => Http::response([
            'data'       => [['store' => ['name' => 'My EasyOrders Store']]],
            'pagination' => ['total' => 42],
        ], 200),
    ]);

    $result = $this->adapter->testConnection(['access_token' => 'test_access_token']);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Connection verified successfully.');
    expect($result->channelInfo['store_name'])->toBe('My EasyOrders Store');
    expect($result->channelInfo['product_count'])->toBe(42);
});

it('fails connection with missing access token', function () {
    $result = $this->adapter->testConnection([]);

    expect($result->success)->toBeFalse();
    expect($result->message)->toBe('Access token is required.');
    expect($result->errors)->toContain('Missing access token');
});

it('creates a product on EasyOrders', function () {
    Http::fake([
        'api.easyorders.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 789, 'name' => 'Test Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test Product', 'price' => '49.99', 'sku' => 'EO-001', 'status' => 'active'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('789');
    expect($result->action)->toBe('created');
});

it('fetches a product from EasyOrders', function () {
    Http::fake([
        'api.easyorders.dev/admin/v2/products/789' => Http::response([
            'data' => [
                'id'          => 789,
                'name'        => 'Fetched Product',
                'description' => 'A test product',
                'price'       => ['amount' => 49.99, 'currency' => 'SAR'],
                'sku'         => 'EO-001',
                'quantity'    => 20,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('789');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched Product');
    expect($result['common']['price'])->toBe(49.99);
    expect($result['common']['sku'])->toBe('EO-001');
    expect($result['common']['status'])->toBe('sale');
});

it('deletes a product from EasyOrders', function () {
    Http::fake([
        'api.easyorders.dev/admin/v2/products/789' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('789');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.easyorders.dev/admin/v2/products' => Http::response([
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

it('builds correct EasyOrders product body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '99.99', 'sku' => 'EO-AR', 'status' => 'active'],
        'locales' => [
            'ar' => ['name' => 'منتج بعمولة', 'description' => 'وصف المنتج'],
            'en' => ['name' => 'Commission Product', 'description' => 'Product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildEasyOrdersProductBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic preferred
    expect($body['name'])->toBe('منتج بعمولة');
    expect($body['description'])->toBe('وصف المنتج');
    expect($body['price']['amount'])->toBe(99.99);
    expect($body['price']['currency'])->toBe('SAR');
    expect($body['sku'])->toBe('EO-AR');
    expect($body['status'])->toBe('sale');
});

it('normalizes EasyOrders product data correctly', function () {
    $data = [
        'name'        => 'Normalized EasyOrders Product',
        'description' => 'EasyOrders description',
        'price'       => ['amount' => 59.99],
        'sale_price'  => ['amount' => 49.99],
        'sku'         => 'EO-NORM',
        'quantity'    => 40,
        'weight'      => 1.2,
        'status'      => 'sale',
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeEasyOrdersProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['name'])->toBe('Normalized EasyOrders Product');
    expect($normalized['common']['price'])->toBe(59.99);
    expect($normalized['common']['sale_price'])->toBe(49.99);
    expect($normalized['common']['sku'])->toBe('EO-NORM');
    expect($normalized['locales'])->toBeEmpty();
});

// ─── Webhook Verification Tests ────────────────────────────────────────

it('verifies webhook with valid HMAC signature', function () {
    $adapter = new EasyOrdersAdapter;
    $adapter->setCredentials(['webhook_secret' => 'eo_webhook_secret']);

    $payload = '{"event":"product.updated"}';
    $signature = hash_hmac('sha256', $payload, 'eo_webhook_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_EASYORDERS_SIGNATURE' => $signature,
    ], $payload);

    expect($adapter->verifyWebhook($request))->toBeTrue();
});

it('rejects webhook with invalid signature', function () {
    $adapter = new EasyOrdersAdapter;
    $adapter->setCredentials(['webhook_secret' => 'eo_webhook_secret']);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_EASYORDERS_SIGNATURE' => 'wrong_signature',
    ], '{"event":"product.updated"}');

    expect($adapter->verifyWebhook($request))->toBeFalse();
});

// ─── Credential Refresh Tests ──────────────────────────────────────────

it('refreshes OAuth credentials successfully', function () {
    Http::fake([
        'accounts.easyorders.sa/oauth2/token' => Http::response([
            'access_token'  => 'new_eo_token',
            'refresh_token' => 'new_eo_refresh',
            'expires_in'    => 3600,
        ], 200),
    ]);

    $adapter = new EasyOrdersAdapter;
    $adapter->setCredentials([
        'refresh_token' => 'old_refresh',
        'client_id'     => 'eo_client',
        'client_secret' => 'eo_secret',
    ]);

    $result = $adapter->refreshCredentials();

    expect($result)->not->toBeNull();
    expect($result['access_token'])->toBe('new_eo_token');
    expect($result['refresh_token'])->toBe('new_eo_refresh');
    expect($result)->toHaveKey('expires_at');
});

it('returns null when refresh token is missing', function () {
    $adapter = new EasyOrdersAdapter;
    $adapter->setCredentials([]);

    expect($adapter->refreshCredentials())->toBeNull();
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
