<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Shopify\Models\ShopifyCredentialsConfig;

use function Pest\Laravel\get;

it('should return the list of attributes', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.shopify.get-attribute'))
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                '*' => [
                    'id',
                    'code',
                    'label',
                ],
            ],
            'page',
            'lastPage',
        ]);

    $data = json_decode($response->getContent(), true);
    $this->assertEquals('sku', $data['options'][0]['code']);
});

it('should return the selected attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();
    $queryParams = http_build_query([
        'entityName'  => json_encode([$attribute->type]),
        'identifiers' => [
            'columnName' => 'code',
            'values'     => [$attribute->code],
        ],
    ]);
    $response = get(route('admin.shopify.get-attribute').'?'.$queryParams)
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                '*' => [
                    'id',
                    'code',
                    'label',
                ],
            ],
            'page',
            'lastPage',
        ]);

    $data = json_decode($response->getContent(), true);
    $this->assertEquals($attribute->code, $data['options'][0]['code']);
    $this->assertEquals(1, $data['page']);
    $this->assertEquals(1, $data['lastPage']);
});

it('should return a list of image attributes', function () {
    $this->loginAsAdmin();

    $imageAttribute = Attribute::factory()->create(['code' => 'image_attr_tst', 'type' => 'image']);

    $response = get(route('admin.shopify.get-image-attribute'));

    $response->assertOk()
        ->assertJsonStructure([
            'options' => [
                '*' => [
                    'id',
                    'code',
                    'label',
                ],
            ],
        ]);

    $attributeData = [
        'id'    => $imageAttribute->id,
        'code'  => $imageAttribute->code,
        'label' => "[{$imageAttribute->code}]",
    ];

    $data = json_decode($response->getContent(), true);
    $this->assertNotEmpty($data['options']);
    $this->assertTrue(
        collect($data['options'])->contains($attributeData)
    );
});

it('should return the list of active Shopify credentials', function () {
    $this->loginAsAdmin();

    $activeCredential1 = ShopifyCredentialsConfig::factory()->create(['active' => 1, 'shopUrl' => 'shop1.myshopify.com']);
    $activeCredential2 = ShopifyCredentialsConfig::factory()->create(['active' => 1, 'shopUrl' => 'shop2.myshopify.com']);
    $inactiveCredential = ShopifyCredentialsConfig::factory()->create(['active' => 0, 'shopUrl' => 'shop3.myshopify.com']);

    $response = get(route('shopify.credential.fetch-all'));

    $response->assertStatus(200);

    $response->assertJsonFragment([
        'id'    => $activeCredential1->id,
        'label' => $activeCredential1->shopUrl,
    ]);
    $response->assertJsonFragment([
        'id'    => $activeCredential2->id,
        'label' => $activeCredential2->shopUrl,
    ]);
});

it('should return an empty list when no active Shopify credentials exist', function () {
    $this->loginAsAdmin();

    $existingCredentials = ShopifyCredentialsConfig::where('active', 1)->get();
    foreach ($existingCredentials as $credential) {
        $credential->update(['active' => 0]);
    }

    $response = get(route('shopify.credential.fetch-all'));

    $response->assertStatus(200);

    $response->assertJson([
        'options' => [],
    ]);
});

it('should return the list of channels', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $response = get(route('shopify.channel.fetch-all'));

    $response->assertStatus(200);

    $response->assertJsonFragment([
        'id'    => $channel->code,
        'label' => $channel->name,
    ]);
});

it('should return the selected channel', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();

    $queryParams = http_build_query([
        'page'                    => 1,
        'locale'                  => 'en_US',
        'identifiers[columnName]' => 'id',
        'identifiers[values][0]'  => $channel->code,
    ]);

    $response = get(route('shopify.channel.fetch-all').'?'.$queryParams);

    $response->assertStatus(200);

    $response->assertJsonFragment([
        'id'    => $channel->code,
        'label' => $channel->name,
    ]);
});

it('should return the list of currencies', function () {
    $this->loginAsAdmin();

    $currency = Currency::factory()->create([
        'code'   => 'DOP',
        'symbol' => '$',
    ]);

    $response = get(route('shopify.currency.fetch-all'));

    $response->assertStatus(200);
});

it('should return the selected currency', function () {
    $this->loginAsAdmin();

    Currency::whereIn('code', ['INR', 'EUR', 'USD'])->update(['status' => 1]);

    $queryParams = http_build_query([
        'page'                    => 1,
        'locale'                  => 'en_US',
        'identifiers[columnName]' => 'id',
        'identifiers[values][0]'  => 'INR',
    ]);

    $response = get(route('shopify.currency.fetch-all').'?'.$queryParams);

    $response->assertStatus(200);

    $data = json_decode($response->getContent(), true);

    $this->assertEquals('INR', $data['options'][0]['id']);
});
