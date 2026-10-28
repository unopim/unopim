<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->headers = $this->getAuthenticationHeaders();
    $this->channel = core()->getDefaultChannel()->code;
    $this->locale = core()->getDefaultChannel()->locales()->first()->code;
    $this->currency = core()->getDefaultChannel()->currencies()->first()->code;
});

function attributeFilterTestAttribute(string $type, array $attributes = []): Attribute
{
    return Attribute::factory()->create(array_merge([
        'type'              => $type,
        'value_per_locale'  => false,
        'value_per_channel' => false,
    ], $attributes));
}

function attributeFilterTestProduct(string $sku, array $values, array $columns = []): Product
{
    $product = Product::factory()->simple()->create(['sku' => $sku]);

    DB::table('products')->where('id', $product->id)->update(array_merge([
        'values' => json_encode(array_replace_recursive($product->values ?? [], $values)),
    ], $columns));

    return $product;
}

function attributeFilterTestSkus(object $test, array $filters, array $query = []): array
{
    $response = $test->withHeaders($test->headers)->json('GET', route('admin.api.products.index', array_merge([
        'filters' => json_encode($filters),
        'limit'   => 100,
    ], $query)));

    $response->assertOk();

    return array_column($response->json('data'), 'sku');
}

it('filters products by a text attribute with = and CONTAINS', function () {
    $attribute = attributeFilterTestAttribute('text');

    attributeFilterTestProduct('TXT-RED', ['common' => [$attribute->code => 'Ruby Red Shoe']]);
    attributeFilterTestProduct('TXT-BLUE', ['common' => [$attribute->code => 'Navy Blue Shoe']]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '=', 'value' => 'ruby red shoe']]]))
        ->toBe(['TXT-RED']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'CONTAINS', 'value' => 'Shoe']]]))
        ->toContain('TXT-RED', 'TXT-BLUE');

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'CONTAINS', 'value' => 'navy']]]))
        ->toBe(['TXT-BLUE']);
});

it('filters products by a select attribute with IN and NOT IN', function () {
    $attribute = attributeFilterTestAttribute('select');

    attributeFilterTestProduct('SEL-ALPHA', ['common' => [$attribute->code => 'alpha']]);
    attributeFilterTestProduct('SEL-BETA', ['common' => [$attribute->code => 'beta']]);
    attributeFilterTestProduct('SEL-GAMMA', ['common' => [$attribute->code => 'gamma']]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'IN', 'value' => ['alpha', 'gamma']]]]))
        ->toEqualCanonicalizing(['SEL-ALPHA', 'SEL-GAMMA']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'NOT IN', 'value' => ['alpha']]]]))
        ->toContain('SEL-BETA', 'SEL-GAMMA')
        ->not->toContain('SEL-ALPHA');
});

it('filters products by a boolean attribute', function () {
    $attribute = attributeFilterTestAttribute('boolean');

    attributeFilterTestProduct('BOOL-ON', ['common' => [$attribute->code => 'true']]);
    attributeFilterTestProduct('BOOL-OFF', ['common' => [$attribute->code => 'false']]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '=', 'value' => true]]]))
        ->toBe(['BOOL-ON']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '=', 'value' => '0']]]))
        ->toBe(['BOOL-OFF']);
});

it('filters products by a price attribute in a currency', function () {
    $attribute = attributeFilterTestAttribute('price');

    attributeFilterTestProduct('PRICE-CHEAP', ['common' => [$attribute->code => [$this->currency => '5.00']]]);
    attributeFilterTestProduct('PRICE-MID', ['common' => [$attribute->code => [$this->currency => '50.00']]]);
    attributeFilterTestProduct('PRICE-HIGH', ['common' => [$attribute->code => [$this->currency => '500.00']]]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '>', 'value' => 10, 'currency' => $this->currency]]]))
        ->toEqualCanonicalizing(['PRICE-MID', 'PRICE-HIGH']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'BETWEEN', 'value' => [10, 100], 'currency' => $this->currency]]]))
        ->toBe(['PRICE-MID']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '=', 'value' => '5', 'currency' => $this->currency]]]))
        ->toBe(['PRICE-CHEAP']);
});

it('rejects a price filter without a currency with 422', function () {
    $attribute = attributeFilterTestAttribute('price');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '>', 'value' => 10]]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on price attribute "%s" requires a "currency".', $attribute->code));
});

it('filters products by a date attribute', function () {
    $attribute = attributeFilterTestAttribute('date');

    attributeFilterTestProduct('DATE-EARLY', ['common' => [$attribute->code => '2026-01-10']]);
    attributeFilterTestProduct('DATE-LATE', ['common' => [$attribute->code => '2026-06-10']]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '<', 'value' => '2026-03-01']]]))
        ->toBe(['DATE-EARLY']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'BETWEEN', 'value' => ['2026-06-01', '2026-06-30']]]]))
        ->toBe(['DATE-LATE']);
});

it('rejects a date filter that is not in the Y-m-d format with 422', function () {
    $attribute = attributeFilterTestAttribute('date');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '<', 'value' => '01/03/2026']]]),
    ]))->assertUnprocessable();
});

it('filters products by EMPTY and NOT EMPTY', function () {
    $attribute = attributeFilterTestAttribute('text');

    attributeFilterTestProduct('EMPTY-FILLED', ['common' => [$attribute->code => 'something']]);
    attributeFilterTestProduct('EMPTY-BLANK', []);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'NOT EMPTY']]]))
        ->toBe(['EMPTY-FILLED']);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'EMPTY']]]))
        ->toContain('EMPTY-BLANK')
        ->not->toContain('EMPTY-FILLED');
});

it('scopes a localizable attribute filter to the requested locale', function () {
    $attribute = attributeFilterTestAttribute('text', ['value_per_locale' => true]);

    attributeFilterTestProduct('LOC-MATCH', ['locale_specific' => [$this->locale => [$attribute->code => 'Localized Name']]]);
    attributeFilterTestProduct('LOC-OTHER', ['locale_specific' => ['xx_XX' => [$attribute->code => 'Localized Name']]]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '=', 'value' => 'Localized Name', 'locale' => $this->locale]]]))
        ->toBe(['LOC-MATCH']);
});

it('scopes a channel and locale specific attribute filter to both', function () {
    $attribute = attributeFilterTestAttribute('text', ['value_per_locale' => true, 'value_per_channel' => true]);

    attributeFilterTestProduct('CL-MATCH', ['channel_locale_specific' => [$this->channel => [$this->locale => [$attribute->code => 'Scoped']]]]);
    attributeFilterTestProduct('CL-OTHER', ['channel_locale_specific' => ['other' => [$this->locale => [$attribute->code => 'Scoped']]]]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [[
        'operator' => '=',
        'value'    => 'Scoped',
        'locale'   => $this->locale,
        'channel'  => $this->channel,
    ]]]))->toBe(['CL-MATCH']);
});

it('rejects a localizable attribute filter without a locale with 422', function () {
    $attribute = attributeFilterTestAttribute('text', ['value_per_locale' => true]);

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => 'x']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on attribute "%s" requires a "locale" because the attribute is localizable.', $attribute->code));
});

it('rejects a channel-specific attribute filter without a channel with 422', function () {
    $attribute = attributeFilterTestAttribute('text', ['value_per_channel' => true]);

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => 'x']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on attribute "%s" requires a "channel" because the attribute is channel-specific.', $attribute->code));
});

it('rejects an unknown locale with 422', function () {
    $attribute = attributeFilterTestAttribute('text', ['value_per_locale' => true]);

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => 'x', 'locale' => "en_US'--"]]]),
    ]))->assertUnprocessable();
});

it('rejects an unknown attribute code with the unsupported filter message', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['no_such_attribute' => [['operator' => '=', 'value' => '1']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', 'Filter on property "no_such_attribute" is not supported or does not support operator "=".');
});

it('rejects an operator the attribute type does not support with 422', function () {
    $attribute = attributeFilterTestAttribute('boolean');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => 'CONTAINS', 'value' => '1']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on property "%s" is not supported or does not support operator "CONTAINS".', $attribute->code));
});

it('rejects an array value for a scalar operator with 422', function () {
    $attribute = attributeFilterTestAttribute('text');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => ['a', 'b']]]]),
    ]))->assertUnprocessable();
});

it('combines attribute filters with the status and updated_at property filters', function () {
    $attribute = attributeFilterTestAttribute('text');

    attributeFilterTestProduct('MIX-MATCH', ['common' => [$attribute->code => 'combo']], ['status' => 1, 'updated_at' => '2031-05-01 00:00:00']);
    attributeFilterTestProduct('MIX-DISABLED', ['common' => [$attribute->code => 'combo']], ['status' => 0, 'updated_at' => '2031-05-01 00:00:00']);
    attributeFilterTestProduct('MIX-STALE', ['common' => [$attribute->code => 'combo']], ['status' => 1, 'updated_at' => '2020-01-01 00:00:00']);
    attributeFilterTestProduct('MIX-OTHER', ['common' => [$attribute->code => 'different']], ['status' => 1, 'updated_at' => '2031-05-01 00:00:00']);

    expect(attributeFilterTestSkus($this, [
        $attribute->code => [['operator' => '=', 'value' => 'combo']],
        'status'         => [['operator' => '=', 'value' => true]],
        'updated_at'     => [['operator' => '>', 'value' => '2031-01-01 00:00:00']],
    ]))->toBe(['MIX-MATCH']);
});

it('walks attribute filtered results through search_after cursor pages', function () {
    $attribute = attributeFilterTestAttribute('text');

    foreach (range(1, 5) as $i) {
        attributeFilterTestProduct("CUR-{$i}", ['common' => [$attribute->code => 'paged']]);
    }

    attributeFilterTestProduct('CUR-SKIP', ['common' => [$attribute->code => 'unpaged']]);

    $filters = json_encode([$attribute->code => [['operator' => '=', 'value' => 'paged']]]);
    $collected = [];
    $searchAfter = null;

    do {
        $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', array_filter([
            'filters'         => $filters,
            'limit'           => 2,
            'pagination_type' => 'search_after',
            'search_after'    => $searchAfter,
        ])))->assertOk();

        $collected = array_merge($collected, array_column($response->json('data'), 'sku'));
        $searchAfter = $response->json('search_after');
    } while ($searchAfter !== null);

    expect($collected)->toBe(['CUR-1', 'CUR-2', 'CUR-3', 'CUR-4', 'CUR-5']);
});

it('filters configurable products by attribute values', function () {
    $attribute = attributeFilterTestAttribute('text');

    $product = Product::factory()->configurable()->create(['sku' => 'CONF-ATTR']);

    DB::table('products')->where('id', $product->id)->update([
        'values' => json_encode(array_replace_recursive($product->values ?? [], ['common' => [$attribute->code => 'configurable value']])),
    ]);

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.configurable_products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => 'configurable value']]]),
    ]))->assertOk();

    expect(array_column($response->json('data'), 'sku'))->toBe(['CONF-ATTR']);
});

it('narrows the listing to the ids Elasticsearch matches when it is enabled', function () {
    $attribute = attributeFilterTestAttribute('select');

    $match = attributeFilterTestProduct('ES-MATCH', ['common' => [$attribute->code => 'one']]);
    attributeFilterTestProduct('ES-MISS', ['common' => [$attribute->code => 'one']]);

    attributeFilterTestEnableElasticsearch();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function (array $args) use ($attribute) {
            expect($args['index'])->toBe('testing_products')
                ->and($args['body']['query']['constant_score']['filter']['bool']['filter'])->toBe([
                    ['terms' => ['values.common.'.$attribute->code.'-select' => ['one']]],
                ]);

            return true;
        })
        ->andReturn(['hits' => ['hits' => [['_id' => (string) $match->id, 'sort' => [$match->id]]]]]);

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => 'IN', 'value' => ['one']]]]))
        ->toBe(['ES-MATCH']);
});

it('returns the same page with Elasticsearch enabled as with the database engine', function () {
    $attribute = attributeFilterTestAttribute('text');

    foreach (range(1, 3) as $i) {
        attributeFilterTestProduct("PARITY-{$i}", ['common' => [$attribute->code => 'parity']]);
    }

    attributeFilterTestProduct('PARITY-NO', ['common' => [$attribute->code => 'other']]);

    $filters = json_encode([$attribute->code => [['operator' => '=', 'value' => 'parity']]]);

    $databaseResponse = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.index', ['filters' => $filters, 'limit' => 2]))
        ->assertOk()
        ->json();

    $matchingIds = Product::query()->whereIn('sku', ['PARITY-1', 'PARITY-2', 'PARITY-3'])->orderBy('id')->pluck('id');

    attributeFilterTestEnableElasticsearch();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->andReturn(['hits' => ['hits' => $matchingIds->map(fn ($id) => ['_id' => (string) $id, 'sort' => [$id]])->all()]]);

    $elasticResponse = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.index', ['filters' => $filters, 'limit' => 2]))
        ->assertOk()
        ->json();

    expect(array_column($elasticResponse['data'], 'sku'))->toBe(array_column($databaseResponse['data'], 'sku'))
        ->and($elasticResponse['total'])->toBe($databaseResponse['total'])
        ->and($elasticResponse['last_page'])->toBe($databaseResponse['last_page'])
        ->and($elasticResponse['total'])->toBe(3);
});

it('falls back to the database engine when Elasticsearch is unreachable', function () {
    $attribute = attributeFilterTestAttribute('text');

    attributeFilterTestProduct('FALLBACK-HIT', ['common' => [$attribute->code => 'fallback']]);
    attributeFilterTestProduct('FALLBACK-MISS', ['common' => [$attribute->code => 'other']]);

    attributeFilterTestEnableElasticsearch();

    ElasticSearch::shouldReceive('search')->once()->andThrow(new RuntimeException('No alive nodes'));

    expect(attributeFilterTestSkus($this, [$attribute->code => [['operator' => '=', 'value' => 'fallback']]]))
        ->toBe(['FALLBACK-HIT']);
});

it('does not query Elasticsearch for property-only filters', function () {
    attributeFilterTestProduct('PROP-ONLY', []);

    attributeFilterTestEnableElasticsearch();

    ElasticSearch::shouldReceive('search')->never();

    expect(attributeFilterTestSkus($this, ['sku' => [['operator' => '=', 'value' => 'PROP-ONLY']]]))
        ->toBe(['PROP-ONLY']);
});

function attributeFilterTestEnableElasticsearch(): void
{
    config([
        'elasticsearch.enabled'                     => true,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
    ]);

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn(Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient'));
}
