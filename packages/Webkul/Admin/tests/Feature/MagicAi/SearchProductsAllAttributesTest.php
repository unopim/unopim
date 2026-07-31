<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\SearchProducts;
use Webkul\Attribute\Models\Attribute;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

it('finds products by a common text attribute value such as ean', function () {
    $admin = $this->loginAsAdmin();

    Attribute::query()->firstOrCreate(
        ['code' => 'ean'],
        [
            'type'              => 'text',
            'validation'        => null,
            'value_per_locale'  => 0,
            'value_per_channel' => 0,
            'is_unique'         => 0,
        ],
    );

    $ean = 'EAN-4006381333931-T'.random_int(1000, 9999);

    $product = Product::factory()->simple()->withInitialValues()->create([
        'sku' => 'SEARCH-EAN-'.random_int(10000, 99999),
    ]);

    $values = $product->values;
    $values['common']['ean'] = $ean;
    $product->values = $values;
    $product->save();

    $result = decodeSearchToolResult(
        app(SearchProducts::class)
            ->register(buildSearchChatContext($admin))
            ->handle(new Request(['query' => $ean]))
    );

    expect($result['total'])->toBeGreaterThan(0);
    expect(array_column($result['products'], 'sku'))->toContain($product->sku);
});

it('finds products by a locale-specific text attribute value', function () {
    $admin = $this->loginAsAdmin();

    $code = 'test_loc_search_attr';

    Attribute::query()->firstOrCreate(
        ['code' => $code],
        [
            'type'              => 'text',
            'validation'        => null,
            'value_per_locale'  => 1,
            'value_per_channel' => 0,
            'is_unique'         => 0,
        ],
    );

    $needle = 'LocaleScopedNeedle'.random_int(1000, 9999);

    $product = Product::factory()->simple()->withInitialValues()->create([
        'sku' => 'SEARCH-LOC-'.random_int(10000, 99999),
    ]);

    $values = $product->values;
    $values['locale_specific']['en_US'][$code] = $needle;
    $product->values = $values;
    $product->save();

    $result = decodeSearchToolResult(
        app(SearchProducts::class)
            ->register(buildSearchChatContext($admin))
            ->handle(new Request(['query' => $needle]))
    );

    expect($result['total'])->toBeGreaterThan(0);
    expect(array_column($result['products'], 'sku'))->toContain($product->sku);
});

it('still finds products by name after dynamic attribute discovery', function () {
    $admin = $this->loginAsAdmin();

    $name = 'DynamicNameNeedle'.random_int(1000, 9999);

    $product = Product::factory()->simple()->withInitialValues()->create([
        'sku' => 'SEARCH-NAME-'.random_int(10000, 99999),
    ]);

    $values = $product->values;
    $values['channel_locale_specific']['default']['en_US']['name'] = $name;
    $product->values = $values;
    $product->save();

    $result = decodeSearchToolResult(
        app(SearchProducts::class)
            ->register(buildSearchChatContext($admin))
            ->handle(new Request(['query' => $name]))
    );

    expect($result['total'])->toBeGreaterThan(0);
    expect(array_column($result['products'], 'sku'))->toContain($product->sku);
});

it('does not crash when the LLM passes a limit argument', function () {
    $admin = $this->loginAsAdmin();

    $result = decodeSearchToolResult(
        app(SearchProducts::class)
            ->register(buildSearchChatContext($admin))
            ->handle(new Request(['query' => 'anything', 'limit' => 5]))
    );

    expect($result)->toHaveKeys(['total', 'products']);
});

function buildSearchChatContext($admin): ChatContext
{
    return new ChatContext(
        message: 'Search products',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
        user: $admin,
    );
}

function decodeSearchToolResult(string $result): array
{
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}
