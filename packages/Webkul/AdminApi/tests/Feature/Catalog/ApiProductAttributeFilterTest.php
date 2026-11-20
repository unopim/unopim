<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\AdminApi\Services\ProductAttributeFilterService;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->clusterConfigured = (bool) config('elasticsearch.enabled');

    config(['elasticsearch.enabled' => false]);

    $this->headers = $this->getAuthenticationHeaders();
    $this->channel = core()->getDefaultChannel()->code;
    $this->locale = core()->getDefaultChannel()->locales()->first()->code;
    $this->currency = core()->getDefaultChannel()->currencies()->first()->code;

    $this->makeAttribute = fn (string $type, array $attributes = []): Attribute => Attribute::factory()->create(array_merge([
        'type'              => $type,
        'value_per_locale'  => false,
        'value_per_channel' => false,
    ], $attributes));

    $this->makeProduct = function (string $sku, array $values, array $columns = []): Product {
        $product = Product::factory()->simple()->create(['sku' => $sku]);

        DB::table('products')->where('id', $product->id)->update(array_merge([
            'values' => json_encode(array_replace_recursive($product->values ?? [], $values)),
        ], $columns));

        return $product;
    };

    $this->listSkus = function (array $filters, array $query = []): array {
        $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', array_merge([
            'filters' => json_encode($filters),
            'limit'   => 100,
        ], $query)));

        $response->assertOk();

        return array_column($response->json('data'), 'sku');
    };

    $this->enableElasticsearch = function (): void {
        config([
            'elasticsearch.enabled'                     => true,
            'elasticsearch.prefix'                      => 'testing',
            'elasticsearch.connection'                  => 'default',
            'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
        ]);

        ElasticSearch::shouldReceive('makeConnection')
            ->andReturn(Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient'));
    };
});

it('filters products by a text attribute with = and CONTAINS', function () {
    $attribute = ($this->makeAttribute)('text');

    ($this->makeProduct)('TXT-RED', ['common' => [$attribute->code => 'Ruby Red Shoe']]);
    ($this->makeProduct)('TXT-BLUE', ['common' => [$attribute->code => 'Navy Blue Shoe']]);

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => 'ruby red shoe']]]))
        ->toBe(['TXT-RED']);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'CONTAINS', 'value' => 'Shoe']]]))
        ->toContain('TXT-RED', 'TXT-BLUE');

    expect(($this->listSkus)([$attribute->code => [['operator' => 'CONTAINS', 'value' => 'navy']]]))
        ->toBe(['TXT-BLUE']);
});

it('filters products by a select attribute with IN and NOT IN', function () {
    $attribute = ($this->makeAttribute)('select');

    $stamp = ['updated_at' => '2033-01-01 00:00:00'];

    ($this->makeProduct)('SEL-ALPHA', ['common' => [$attribute->code => 'alpha']], $stamp);
    ($this->makeProduct)('SEL-BETA', ['common' => [$attribute->code => 'beta']], $stamp);
    ($this->makeProduct)('SEL-GAMMA', ['common' => [$attribute->code => 'gamma']], $stamp);

    $window = ['updated_at' => [['operator' => 'BETWEEN', 'value' => ['2032-12-31 00:00:00', '2033-01-02 00:00:00']]]];

    expect(($this->listSkus)([$attribute->code => [['operator' => 'IN', 'value' => ['alpha', 'gamma']]]]))
        ->toEqualCanonicalizing(['SEL-ALPHA', 'SEL-GAMMA']);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'NOT IN', 'value' => ['alpha']]]] + $window))
        ->toEqualCanonicalizing(['SEL-BETA', 'SEL-GAMMA']);
});

it('keeps products without a value in NOT IN results', function (): void {
    $attribute = ($this->makeAttribute)('select');
    $stamp = ['updated_at' => '2033-02-01 00:00:00'];

    ($this->makeProduct)('NOTIN-RED', ['common' => [$attribute->code => 'red']], $stamp);
    ($this->makeProduct)('NOTIN-BLUE', ['common' => [$attribute->code => 'blue']], $stamp);
    ($this->makeProduct)('NOTIN-MISSING', [], $stamp);

    expect(($this->listSkus)([
        $attribute->code => [['operator' => 'NOT IN', 'value' => ['red']]],
        'updated_at'     => [['operator' => 'BETWEEN', 'value' => ['2033-01-31 00:00:00', '2033-02-02 00:00:00']]],
    ]))->toBe(['NOTIN-BLUE', 'NOTIN-MISSING']);
});

it('matches whole option codes only with IN and NOT IN', function () {
    $attribute = ($this->makeAttribute)('multiselect');

    $stamp = ['updated_at' => '2033-04-01 00:00:00'];

    ($this->makeProduct)('OPT-RED', ['common' => [$attribute->code => 'red']], $stamp);
    ($this->makeProduct)('OPT-RED-DARK', ['common' => [$attribute->code => 'red_dark']], $stamp);
    ($this->makeProduct)('OPT-MIXED', ['common' => [$attribute->code => 'blue,red']], $stamp);

    $window = ['updated_at' => [['operator' => 'BETWEEN', 'value' => ['2033-03-31 00:00:00', '2033-04-02 00:00:00']]]];

    expect(($this->listSkus)([$attribute->code => [['operator' => 'IN', 'value' => ['red']]]]))
        ->toEqualCanonicalizing(['OPT-RED', 'OPT-MIXED']);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'NOT IN', 'value' => ['red']]]] + $window))
        ->toBe(['OPT-RED-DARK']);
});

it('rejects an option filter value that is not an option code with 422', function () {
    $attribute = ($this->makeAttribute)('select');

    ($this->makeProduct)('OPT-REGEX', ['common' => [$attribute->code => 'red']]);

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => 'IN', 'value' => ['(']]]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter "%s" expects option codes as values.', $attribute->code));
});

it('filters products by a boolean attribute', function () {
    $attribute = ($this->makeAttribute)('boolean');

    ($this->makeProduct)('BOOL-ON', ['common' => [$attribute->code => 'true']]);
    ($this->makeProduct)('BOOL-OFF', ['common' => [$attribute->code => 'false']]);

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => true]]]))
        ->toBe(['BOOL-ON']);

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => '0']]]))
        ->toBe(['BOOL-OFF']);
});

it('filters products by a price attribute in a currency', function () {
    $attribute = ($this->makeAttribute)('price');

    ($this->makeProduct)('PRICE-CHEAP', ['common' => [$attribute->code => [$this->currency => '5.00']]]);
    ($this->makeProduct)('PRICE-MID', ['common' => [$attribute->code => [$this->currency => '50.00']]]);
    ($this->makeProduct)('PRICE-HIGH', ['common' => [$attribute->code => [$this->currency => '500.00']]]);

    expect(($this->listSkus)([$attribute->code => [['operator' => '>', 'value' => 10, 'currency' => $this->currency]]]))
        ->toEqualCanonicalizing(['PRICE-MID', 'PRICE-HIGH']);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'BETWEEN', 'value' => [10, 100], 'currency' => $this->currency]]]))
        ->toBe(['PRICE-MID']);

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => '5', 'currency' => $this->currency]]]))
        ->toBe(['PRICE-CHEAP']);
});

it('rejects a price filter without a currency with 422', function () {
    $attribute = ($this->makeAttribute)('price');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '>', 'value' => 10]]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on price attribute "%s" requires a "currency".', $attribute->code));
});

it('filters products by a date attribute', function () {
    $attribute = ($this->makeAttribute)('date');

    ($this->makeProduct)('DATE-EARLY', ['common' => [$attribute->code => '2026-01-10']]);
    ($this->makeProduct)('DATE-LATE', ['common' => [$attribute->code => '2026-06-10']]);

    expect(($this->listSkus)([$attribute->code => [['operator' => '<', 'value' => '2026-03-01']]]))
        ->toBe(['DATE-EARLY']);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'BETWEEN', 'value' => ['2026-06-01', '2026-06-30']]]]))
        ->toBe(['DATE-LATE']);
});

it('rejects a date filter that is not in the Y-m-d format with 422', function () {
    $attribute = ($this->makeAttribute)('date');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '<', 'value' => '01/03/2026']]]),
    ]))->assertUnprocessable();
});

it('treats a blank string and a missing value as EMPTY', function () {
    $attribute = ($this->makeAttribute)('text');
    $stamp = ['updated_at' => '2032-07-01 00:00:00'];

    ($this->makeProduct)('EMPTY-FILLED', ['common' => [$attribute->code => 'something']], $stamp);
    ($this->makeProduct)('EMPTY-BLANK', ['common' => [$attribute->code => '']], $stamp);
    ($this->makeProduct)('EMPTY-MISSING', [], $stamp);

    $window = ['updated_at' => [['operator' => 'BETWEEN', 'value' => ['2032-06-30 00:00:00', '2032-07-02 00:00:00']]]];

    expect(($this->listSkus)([$attribute->code => [['operator' => 'NOT EMPTY']]] + $window))
        ->toBe(['EMPTY-FILLED']);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'EMPTY']]] + $window))
        ->toBe(['EMPTY-BLANK', 'EMPTY-MISSING']);
});

it('scopes a localizable attribute filter to the requested locale', function () {
    $attribute = ($this->makeAttribute)('text', ['value_per_locale' => true]);

    ($this->makeProduct)('LOC-MATCH', ['locale_specific' => [$this->locale => [$attribute->code => 'Localized Name']]]);
    ($this->makeProduct)('LOC-OTHER', ['locale_specific' => ['xx_XX' => [$attribute->code => 'Localized Name']]]);

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => 'Localized Name', 'locale' => $this->locale]]]))
        ->toBe(['LOC-MATCH']);
});

it('scopes a channel and locale specific attribute filter to both', function () {
    $attribute = ($this->makeAttribute)('text', ['value_per_locale' => true, 'value_per_channel' => true]);

    ($this->makeProduct)('CL-MATCH', ['channel_locale_specific' => [$this->channel => [$this->locale => [$attribute->code => 'Scoped']]]]);
    ($this->makeProduct)('CL-OTHER', ['channel_locale_specific' => ['other' => [$this->locale => [$attribute->code => 'Scoped']]]]);

    expect(($this->listSkus)([$attribute->code => [[
        'operator' => '=',
        'value'    => 'Scoped',
        'locale'   => $this->locale,
        'channel'  => $this->channel,
    ]]]))->toBe(['CL-MATCH']);
});

it('rejects a localizable attribute filter without a locale with 422', function () {
    $attribute = ($this->makeAttribute)('text', ['value_per_locale' => true]);

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => 'x']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on attribute "%s" requires a "locale" because the attribute is localizable.', $attribute->code));
});

it('rejects a channel-specific attribute filter without a channel with 422', function () {
    $attribute = ($this->makeAttribute)('text', ['value_per_channel' => true]);

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => 'x']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on attribute "%s" requires a "channel" because the attribute is channel-specific.', $attribute->code));
});

it('rejects an unknown locale with 422', function () {
    $attribute = ($this->makeAttribute)('text', ['value_per_locale' => true]);

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
    $attribute = ($this->makeAttribute)('boolean');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => 'CONTAINS', 'value' => '1']]]),
    ]))->assertUnprocessable()
        ->assertJsonPath('message', sprintf('Filter on property "%s" is not supported or does not support operator "CONTAINS".', $attribute->code));
});

it('rejects an array value for a scalar operator with 422', function () {
    $attribute = ($this->makeAttribute)('text');

    $this->withHeaders($this->headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode([$attribute->code => [['operator' => '=', 'value' => ['a', 'b']]]]),
    ]))->assertUnprocessable();
});

it('combines attribute filters with the status and updated_at property filters', function () {
    $attribute = ($this->makeAttribute)('text');

    ($this->makeProduct)('MIX-MATCH', ['common' => [$attribute->code => 'combo']], ['status' => 1, 'updated_at' => '2031-05-01 00:00:00']);
    ($this->makeProduct)('MIX-DISABLED', ['common' => [$attribute->code => 'combo']], ['status' => 0, 'updated_at' => '2031-05-01 00:00:00']);
    ($this->makeProduct)('MIX-STALE', ['common' => [$attribute->code => 'combo']], ['status' => 1, 'updated_at' => '2020-01-01 00:00:00']);
    ($this->makeProduct)('MIX-OTHER', ['common' => [$attribute->code => 'different']], ['status' => 1, 'updated_at' => '2031-05-01 00:00:00']);

    expect(($this->listSkus)([
        $attribute->code => [['operator' => '=', 'value' => 'combo']],
        'status'         => [['operator' => '=', 'value' => true]],
        'updated_at'     => [['operator' => '>', 'value' => '2031-01-01 00:00:00']],
    ]))->toBe(['MIX-MATCH']);
});

it('walks attribute filtered results through search_after cursor pages', function () {
    $attribute = ($this->makeAttribute)('text');

    foreach (range(1, 5) as $i) {
        ($this->makeProduct)("CUR-{$i}", ['common' => [$attribute->code => 'paged']]);
    }

    ($this->makeProduct)('CUR-SKIP', ['common' => [$attribute->code => 'unpaged']]);

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
    $attribute = ($this->makeAttribute)('text');

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
    $attribute = ($this->makeAttribute)('select');

    $match = ($this->makeProduct)('ES-MATCH', ['common' => [$attribute->code => 'one']]);
    ($this->makeProduct)('ES-MISS', ['common' => [$attribute->code => 'one']]);

    ($this->enableElasticsearch)();

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

    expect(($this->listSkus)([$attribute->code => [['operator' => 'IN', 'value' => ['one']]]]))
        ->toBe(['ES-MATCH']);
});

it('returns the same page with Elasticsearch enabled as with the database engine', function () {
    $attribute = ($this->makeAttribute)('text');

    foreach (range(1, 3) as $i) {
        ($this->makeProduct)("PARITY-{$i}", ['common' => [$attribute->code => 'parity']]);
    }

    ($this->makeProduct)('PARITY-NO', ['common' => [$attribute->code => 'other']]);

    $filters = json_encode([$attribute->code => [['operator' => '=', 'value' => 'parity']]]);

    $databaseResponse = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.index', ['filters' => $filters, 'limit' => 2]))
        ->assertOk()
        ->json();

    $matchingIds = Product::query()->whereIn('sku', ['PARITY-1', 'PARITY-2', 'PARITY-3'])->orderBy('id')->pluck('id');

    ($this->enableElasticsearch)();

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
    $attribute = ($this->makeAttribute)('text');

    ($this->makeProduct)('FALLBACK-HIT', ['common' => [$attribute->code => 'fallback']]);
    ($this->makeProduct)('FALLBACK-MISS', ['common' => [$attribute->code => 'other']]);

    ($this->enableElasticsearch)();

    ElasticSearch::shouldReceive('search')->once()->andThrow(new RuntimeException('No alive nodes'));

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => 'fallback']]]))
        ->toBe(['FALLBACK-HIT']);
});

it('does not query Elasticsearch for property-only filters', function () {
    ($this->makeProduct)('PROP-ONLY', []);

    ($this->enableElasticsearch)();

    ElasticSearch::shouldReceive('search')->never();

    expect(($this->listSkus)(['sku' => [['operator' => '=', 'value' => 'PROP-ONLY']]]))
        ->toBe(['PROP-ONLY']);
});

it('enforces the attribute filter when sku and status property filters are combined', function () {
    $attribute = ($this->makeAttribute)('text');

    ($this->makeProduct)('COMBO-HIT', ['common' => [$attribute->code => 'combo']], ['status' => 1]);
    ($this->makeProduct)('COMBO-OFF', ['common' => [$attribute->code => 'combo']], ['status' => 0]);
    ($this->makeProduct)('COMBO-OTHER', ['common' => [$attribute->code => 'different']], ['status' => 1]);
    ($this->makeProduct)('COMBO-OTHER-OFF', ['common' => [$attribute->code => 'different']], ['status' => 0]);

    expect(($this->listSkus)([
        'sku'            => [['operator' => 'IN', 'value' => ['COMBO-HIT', 'COMBO-OTHER']]],
        'status'         => [['operator' => '=', 'value' => true]],
        $attribute->code => [['operator' => '=', 'value' => 'combo']],
    ]))->toBe(['COMBO-HIT']);
});

it('enforces the attribute filter on the Elasticsearch path when sku and status are combined', function () {
    $attribute = ($this->makeAttribute)('text');

    $hit = ($this->makeProduct)('ESCOMBO-HIT', ['common' => [$attribute->code => 'combo']], ['status' => 1]);
    $off = ($this->makeProduct)('ESCOMBO-OFF', ['common' => [$attribute->code => 'combo']], ['status' => 0]);
    $other = ($this->makeProduct)('ESCOMBO-OTHER', ['common' => [$attribute->code => 'different']], ['status' => 1]);

    ($this->enableElasticsearch)();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->andReturn(['hits' => ['hits' => [['_id' => (string) $hit->id, 'sort' => [$hit->id]], ['_id' => (string) $off->id, 'sort' => [$off->id]]]]]);

    expect(($this->listSkus)([
        'sku'            => [['operator' => 'IN', 'value' => [$hit->sku, $off->sku, $other->sku]]],
        'status'         => [['operator' => '=', 'value' => true]],
        $attribute->code => [['operator' => '=', 'value' => 'combo']],
    ]))->toBe(['ESCOMBO-HIT']);
});

it('sends NOT IN to Elasticsearch as a negated terms clause', function () {
    $attribute = ($this->makeAttribute)('select');

    $match = ($this->makeProduct)('ESNOT-MATCH', ['common' => [$attribute->code => 'blue']]);
    ($this->makeProduct)('ESNOT-MISS', ['common' => [$attribute->code => 'red']]);

    ($this->enableElasticsearch)();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function (array $args) use ($attribute) {
            expect($args['body']['query']['constant_score']['filter']['bool']['must_not'])->toBe([
                ['terms' => ['values.common.'.$attribute->code.'-select' => ['red']]],
            ]);

            return true;
        })
        ->andReturn(['hits' => ['hits' => [['_id' => (string) $match->id, 'sort' => [$match->id]]]]]);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'NOT IN', 'value' => ['red']]]]))
        ->toBe(['ESNOT-MATCH']);
});

it('falls back to the database engine when Elasticsearch matches more products than the cap', function () {
    $attribute = ($this->makeAttribute)('text');

    $first = ($this->makeProduct)('CAP-HIT', ['common' => [$attribute->code => 'cap']]);
    $second = ($this->makeProduct)('CAP-HIT-TWO', ['common' => [$attribute->code => 'cap']]);
    ($this->makeProduct)('CAP-MISS', ['common' => [$attribute->code => 'other']]);

    app()->bind(ProductAttributeFilterService::class, fn ($app) => new class($app->make(AttributeService::class), $app->make(LocaleRepository::class), $app->make(ChannelRepository::class), $app->make(CurrencyRepository::class)) extends ProductAttributeFilterService
    {
        protected int $maxElasticsearchMatches = 1;
    });

    ($this->enableElasticsearch)();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->andReturn(['hits' => ['hits' => [
            ['_id' => (string) $first->id, 'sort' => [$first->id]],
            ['_id' => (string) $second->id, 'sort' => [$second->id]],
        ]]]);

    expect(($this->listSkus)([$attribute->code => [['operator' => '=', 'value' => 'cap']]]))
        ->toBe(['CAP-HIT', 'CAP-HIT-TWO']);
});

it('treats percent and underscore in a CONTAINS value as literal characters', function () {
    $attribute = ($this->makeAttribute)('text');

    ($this->makeProduct)('LIKE-LITERAL', ['common' => [$attribute->code => '50% off_sale']]);
    ($this->makeProduct)('LIKE-OTHER', ['common' => [$attribute->code => '50 cents offxsale']]);

    expect(($this->listSkus)([$attribute->code => [['operator' => 'CONTAINS', 'value' => '% off_']]]))
        ->toBe(['LIKE-LITERAL']);
});

it('returns the same products from a real Elasticsearch index as from the database', function (): void {
    if (! $this->clusterConfigured) {
        $this->markTestSkipped('Needs a reachable Elasticsearch cluster (ELASTICSEARCH_ENABLED=true).');
    }

    $select = ($this->makeAttribute)('select');
    $text = ($this->makeAttribute)('text');
    $stamp = ['updated_at' => '2033-03-01 00:00:00'];

    ($this->makeProduct)('PARITY-RED', ['common' => [$select->code => 'red', $text->code => 'filled']], $stamp);
    ($this->makeProduct)('PARITY-BLUE', ['common' => [$select->code => 'blue', $text->code => 'filled']], $stamp);
    ($this->makeProduct)('PARITY-MISSING', [], $stamp);

    $window = ['updated_at' => [['operator' => 'BETWEEN', 'value' => ['2033-02-28 00:00:00', '2033-03-02 00:00:00']]]];

    $criteria = [
        [$select->code => [['operator' => 'IN', 'value' => ['red']]]],
        [$select->code => [['operator' => 'NOT IN', 'value' => ['red']]]],
        [$text->code   => [['operator' => 'EMPTY']]],
        [$text->code   => [['operator' => 'NOT EMPTY']]],
    ];

    $listAll = fn (): array => array_map(fn (array $filters): array => ($this->listSkus)($filters + $window), $criteria);

    $database = $listAll();

    $prefix = 'parity_'.Str::lower(Str::random(10));

    config(['elasticsearch.enabled' => true, 'elasticsearch.prefix' => $prefix]);

    try {
        Artisan::call('unopim:product:index', ['--fresh' => true]);

        ElasticSearch::indices()->refresh(['index' => $prefix.'_products']);

        $elasticsearch = $listAll();
    } finally {
        ElasticSearch::indices()->delete(['index' => $prefix.'_products']);
    }

    expect($database)->toBe([
        ['PARITY-RED'],
        ['PARITY-BLUE', 'PARITY-MISSING'],
        ['PARITY-MISSING'],
        ['PARITY-RED', 'PARITY-BLUE'],
    ])->and($elasticsearch)->toBe($database);
});
