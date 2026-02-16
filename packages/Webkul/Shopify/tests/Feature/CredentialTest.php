<?php

use Illuminate\Support\Facades\Http;
use Webkul\Shopify\Models\ShopifyCredentialsConfig;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

it('should returns the shopify credential index page', function () {
    $this->loginAsAdmin();

    get(route('shopify.credentials.index'))
        ->assertStatus(200)
        ->assertSeeText(trans('shopify::app.shopify.credential.index.title'));
});

it('should returns the shopify credential edit page', function () {
    $this->loginAsAdmin();

    $shopifyCredential = ShopifyCredentialsConfig::factory()->create();

    get(route('shopify.credentials.edit', ['id' => $shopifyCredential->id]))
        ->assertStatus(200);
});

it('should create the shopify credential with valid input', function () {
    $this->loginAsAdmin();

    Http::fake([
        'https://test.myshopify.com/admin/api/2023-04/graphql.json' => Http::response(['code' => 200], 200),
    ]);

    $shopifyCredential = [
        'accessToken' => 'test_access_token',
        'apiVersion'  => '2023-04',
        'shopUrl'     => 'https://test.myshopify.com',
    ];

    post(route('shopify.credentials.store'), $shopifyCredential)
        ->assertStatus(200);
});

it('should return error for invalid URL during credential create', function () {
    $this->loginAsAdmin();

    $shopifyCredential = [
        'accessToken' => 'test_access_token',
        'apiVersion'  => '2023-04',
        'shopUrl'     => 'test.myshopify.com',
    ];

    $response = post(route('shopify.credentials.store'), $shopifyCredential)
        ->assertStatus(422);

    $this->assertArrayHasKey('errors', $response->json());
    $this->assertEquals(trans('shopify::app.shopify.credential.invalidurl'), $response->json('errors.shopUrl.0'));
});

it('should return error for invalid credentials ', function () {
    $this->loginAsAdmin();

    Http::fake([
        'https://test.myshopify.com/admin/api/2023-04/graphql.json' => Http::response(['code' => 401], 401),
    ]);

    $shopifyCredential = [
        'accessToken' => 'test_access_token',
        'apiVersion'  => '2023-04',
        'shopUrl'     => 'https://test.myshopify.com',
    ];

    $response = post(route('shopify.credentials.store'), $shopifyCredential)
        ->assertStatus(422);

    $this->assertArrayHasKey('errors', $response->json());
    $this->assertEquals(trans('shopify::app.shopify.credential.invalid'), $response->json('errors.shopUrl.0'));
    $this->assertEquals(trans('shopify::app.shopify.credential.invalid'), $response->json('errors.accessToken.0'));
});

it('should update the shopify credential successfully', function () {
    $this->loginAsAdmin();

    $credential = ShopifyCredentialsConfig::factory()->create([
        'accessToken' => 'valid_access_token',
        'shopUrl'     => 'https://test.myshopify.com',
        'apiVersion'  => '2023-04',
    ]);

    Http::fake([
        'https://test.myshopify.com/admin/api/2023-04/graphql.json' => Http::response(['code' => 200], 200),
    ]);

    $updatedData = [
        'shopUrl'      => 'https://test.myshopify.com',
        'accessToken'  => 'valid_access_token',
        'storeLocales' => json_encode([['locale' => 'en', 'primary' => true]]),
        'salesChannel' => 'online',
        'locations'    => 'location1',
        'apiVersion'   => '2023-04',
    ];

    $response = $this->put(route('shopify.credentials.update', ['id' => $credential->id]), $updatedData);

    $response->assertRedirect(route('shopify.credentials.edit', ['id' => $credential->id]));
    $response->assertSessionHas('success', trans('shopify::app.shopify.credential.update-success'));

    $this->assertDatabaseHas('wk_shopify_credentials_config', [
        'id'      => $credential->id,
        'shopUrl' => 'https://test.myshopify.com',
    ]);
});

it('should returns the shopify credential edit page, with validation', function () {
    $this->loginAsAdmin();

    $shopifyCredential = ShopifyCredentialsConfig::factory()->create();
    $updatedCredential = [
        'id'           => $shopifyCredential->id,
        'accessToken'  => $shopifyCredential->accessToken,
        'apiVersion'   => $shopifyCredential->apiVersion,
        'shopUrl'      => $shopifyCredential->shopUrl,
        'storeLocales' => [],
        'active'       => 0,
    ];

    put(route('shopify.credentials.update', $shopifyCredential->id), $updatedCredential)
        ->assertStatus(302)
        ->assertSessionHasErrors(['shopUrl', 'accessToken']);
});

it('should delete the shopify credential', function () {
    $this->loginAsAdmin();

    $shopifyCredential = ShopifyCredentialsConfig::factory()->create();

    delete(route('shopify.credentials.delete', $shopifyCredential->id))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(ShopifyCredentialsConfig::class), [
        'id' => $shopifyCredential->id,
    ]);
});
