<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\ValueObjects\SyncResult;
use Webkul\Ebay\Adapters\EbayAdapter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->adapter = new EbayAdapter;
    $this->adapter->setCredentials([
        'access_token'  => 'test_access_token',
        'refresh_token' => 'test_refresh_token',
        'client_id'     => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'currency'      => 'SAR',
    ]);
    $this->adapter->setConnectorId(1);
});

it('creates a new product on eBay via syncProduct', function () {
    Http::fake([
        'api.ebay.dev/admin/v2/products' => Http::response([
            'data' => ['id' => 77001, 'name' => 'Test eBay Product'],
        ], 200),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Test eBay Product', 'price' => '49.99', 'sku' => 'EBAY-001', 'status' => 'active'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result)->toBeInstanceOf(SyncResult::class);
    expect($result->success)->toBeTrue();
    expect($result->externalId)->toBe('77001');
    expect($result->action)->toBe('created');
});

it('fetches and normalizes a product from eBay', function () {
    Http::fake([
        'api.ebay.dev/admin/v2/products/77001' => Http::response([
            'data' => [
                'id'          => 77001,
                'name'        => 'Fetched eBay Product',
                'description' => 'A test eBay product',
                'price'       => ['amount' => 49.99, 'currency' => 'SAR'],
                'sku'         => 'EBAY-001',
                'quantity'    => 25,
                'status'      => 'sale',
            ],
        ], 200),
    ]);

    $result = $this->adapter->fetchProduct('77001');

    expect($result)->not->toBeNull();
    expect($result['common']['name'])->toBe('Fetched eBay Product');
    expect($result['common']['price'])->toBe(49.99);
    expect($result['common']['sku'])->toBe('EBAY-001');
    expect($result['common']['status'])->toBe('sale');
});

it('returns null for non-existent eBay product', function () {
    Http::fake([
        'api.ebay.dev/admin/v2/products/99999' => Http::response(null, 404),
    ]);

    $result = $this->adapter->fetchProduct('99999');

    expect($result)->toBeNull();
});

it('deletes a product from eBay', function () {
    Http::fake([
        'api.ebay.dev/admin/v2/products/77001' => Http::response(null, 200),
    ]);

    $result = $this->adapter->deleteProduct('77001');

    expect($result)->toBeTrue();
});

it('handles sync failure gracefully', function () {
    Http::fake([
        'api.ebay.dev/admin/v2/products' => Http::response([
            'error' => ['message' => 'Invalid product data'],
        ], 400),
    ]);

    $product = Product::factory()->create();
    $payload = [
        'common'  => ['name' => 'Bad Product', 'sku' => 'BAD-EBAY'],
        'locales' => [],
    ];

    $result = $this->adapter->syncProduct($product, $payload);

    expect($result->success)->toBeFalse();
    expect($result->action)->toBe('failed');
});

it('builds correct eBay product body with Arabic locale', function () {
    $payload = [
        'common'  => ['price' => '59.99', 'sku' => 'EBAY-AR', 'status' => 'active', 'quantity' => 5],
        'locales' => [
            'ar' => ['name' => 'منتج إيباي', 'description' => 'وصف إيباي'],
            'en' => ['name' => 'eBay Product', 'description' => 'eBay product description'],
        ],
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('buildEbayProductBody');
    $body = $method->invoke($this->adapter, $payload);

    // Arabic should be preferred
    expect($body['name'])->toBe('منتج إيباي');
    expect($body['description'])->toBe('وصف إيباي');
    expect($body['price']['amount'])->toBe(59.99);
    expect($body['price']['currency'])->toBe('SAR');
    expect($body['sku'])->toBe('EBAY-AR');
    expect($body['status'])->toBe('sale');
    expect($body['quantity'])->toBe(5);
});

it('normalizes eBay product data correctly', function () {
    $data = [
        'name'        => 'Normalized eBay Product',
        'description' => 'Normalized description',
        'price'       => ['amount' => 79.99],
        'sale_price'  => ['amount' => 59.99],
        'sku'         => 'NORM-EBAY',
        'quantity'    => 100,
        'weight'      => 2.5,
        'status'      => 'sale',
    ];

    $reflection = new ReflectionClass($this->adapter);
    $method = $reflection->getMethod('normalizeEbayProduct');
    $normalized = $method->invoke($this->adapter, $data);

    expect($normalized)->toHaveKey('common');
    expect($normalized)->toHaveKey('locales');
    expect($normalized['common']['name'])->toBe('Normalized eBay Product');
    expect($normalized['common']['price'])->toBe(79.99);
    expect($normalized['common']['sale_price'])->toBe(59.99);
    expect($normalized['common']['sku'])->toBe('NORM-EBAY');
    expect($normalized['common']['quantity'])->toBe(100);
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

it('tests connection to eBay API', function () {
    Http::fake([
        'api.ebay.dev/admin/v2/products*' => Http::response([
            'data'       => [['store' => ['name' => 'My eBay Store']]],
            'pagination' => ['total' => 300],
        ], 200),
    ]);

    $result = $this->adapter->testConnection(['access_token' => 'valid_ebay_token']);

    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Connection verified successfully.');
    expect($result->channelInfo['store_name'])->toBe('My eBay Store');
});

it('fails connection with missing access token', function () {
    $result = $this->adapter->testConnection([]);

    expect($result->success)->toBeFalse();
    expect($result->errors)->toContain('Missing access token');
});

// ─── Webhook Verification Tests ────────────────────────────────────────

it('verifies webhook with valid HMAC signature', function () {
    $adapter = new EbayAdapter;
    $adapter->setCredentials(['webhook_secret' => 'ebay_webhook_secret']);

    $payload = '{"event":"product.updated"}';
    $signature = hash_hmac('sha256', $payload, 'ebay_webhook_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_EBAY_SIGNATURE' => $signature,
    ], $payload);

    expect($adapter->verifyWebhook($request))->toBeTrue();
});

it('rejects webhook with invalid signature', function () {
    $adapter = new EbayAdapter;
    $adapter->setCredentials(['webhook_secret' => 'ebay_webhook_secret']);

    $request = Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_EBAY_SIGNATURE' => 'wrong_signature',
    ], '{"event":"product.updated"}');

    expect($adapter->verifyWebhook($request))->toBeFalse();
});

// ─── Credential Refresh Tests ──────────────────────────────────────────

it('refreshes OAuth credentials successfully', function () {
    Http::fake([
        'accounts.ebay.sa/oauth2/token' => Http::response([
            'access_token'  => 'new_ebay_token',
            'refresh_token' => 'new_ebay_refresh',
            'expires_in'    => 3600,
        ], 200),
    ]);

    $adapter = new EbayAdapter;
    $adapter->setCredentials([
        'refresh_token' => 'old_refresh',
        'client_id'     => 'ebay_client',
        'client_secret' => 'ebay_secret',
    ]);

    $result = $adapter->refreshCredentials();

    expect($result)->not->toBeNull();
    expect($result['access_token'])->toBe('new_ebay_token');
    expect($result['refresh_token'])->toBe('new_ebay_refresh');
    expect($result)->toHaveKey('expires_at');
});

it('returns null when refresh token is missing', function () {
    $adapter = new EbayAdapter;
    $adapter->setCredentials([]);

    expect($adapter->refreshCredentials())->toBeNull();
});
