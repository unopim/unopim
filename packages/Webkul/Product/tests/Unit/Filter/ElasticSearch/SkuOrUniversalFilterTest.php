<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\ElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\ElasticSearch\SkuOrUniversalFilter;

/*
 * The quick search fans out over the field list it is handed. Whatever that
 * list is, the query it emits must be one Elasticsearch evaluates against the
 * term: an empty `bool` is answered with a match_all, because Elasticsearch
 * applies minimum_should_match only once there are `should` clauses to count,
 * so a term no field can answer would return the whole catalogue.
 */

beforeEach(function () {
    config(['elasticsearch.enabled' => true]);
});

function quickSearchAttribute(string $code, string $type): Attribute
{
    $attribute = new Attribute;

    $attribute->code = $code;
    $attribute->type = $type;
    $attribute->value_per_locale = false;
    $attribute->value_per_channel = false;

    return $attribute;
}

/**
 * A filter resolving only the given codes; every other code resolves to null,
 * the way a configured code that was never created behaves in production.
 *
 * @param  array<string, Attribute>  $attributes
 */
function quickSearchFilter(array $attributes): SkuOrUniversalFilter
{
    $attributeService = Mockery::mock(AttributeService::class);

    $attributeService->shouldReceive('findAttributeByCode')
        ->andReturnUsing(fn (string $code): ?Attribute => $attributes[$code] ?? null);

    return new SkuOrUniversalFilter($attributeService);
}

/**
 * The single `filter` clause one quick search put on the query.
 *
 * @param  array<int, string>  $fields
 * @return array<string, mixed>
 */
function quickSearchClause(SkuOrUniversalFilter $filter, array $fields): array
{
    $query = new ElasticSearchQuery;

    $filter->setQueryManager($query);

    $filter->applyUnfilteredFilter($fields, FilterOperators::WILDCARD, 'test', [
        'locale'  => 'en_US',
        'channel' => 'default',
    ]);

    return $query->build()['query']['constant_score']['filter']['bool']['filter'][0];
}

/**
 * The `should` clauses one quick search accumulated.
 *
 * @param  array<int, string>  $fields
 * @return array<int, mixed>
 */
function quickSearchShouldClauses(SkuOrUniversalFilter $filter, array $fields): array
{
    return quickSearchClause($filter, $fields)['bool']['should'];
}

it('emits one clause per searchable field', function () {
    $filter = quickSearchFilter([
        'sku'      => quickSearchAttribute('sku', Attribute::TEXT_TYPE),
        'ean_gtin' => quickSearchAttribute('ean_gtin', Attribute::TEXT_TYPE),
    ]);

    expect(quickSearchShouldClauses($filter, ['sku', 'ean_gtin']))->toHaveCount(2);
});

it('emits the cheap match_phrase_prefix clause rather than a wildcard', function () {
    $filter = quickSearchFilter(['sku' => quickSearchAttribute('sku', Attribute::TEXT_TYPE)]);

    $clauses = quickSearchShouldClauses($filter, ['sku']);

    expect($clauses[0])->toHaveKey('match_phrase_prefix');
    expect($clauses[0])->not->toHaveKey('wildcard');
});

it('skips a code that resolves to no attribute', function () {
    $filter = quickSearchFilter(['sku' => quickSearchAttribute('sku', Attribute::TEXT_TYPE)]);

    expect(quickSearchShouldClauses($filter, ['sku', 'gone_attribute']))->toHaveCount(1);
});

it('matches no products when not one field resolves, instead of returning every product', function () {
    $filter = quickSearchFilter([]);

    $clause = quickSearchClause($filter, ['gone_attribute', 'another_gone_attribute']);

    /*
     * An empty `should` with a minimum_should_match of 1 is NOT an empty
     * result set: Elasticsearch builds the Lucene BooleanQuery first, returns
     * a MatchAllDocsQuery for a bool query that ended up with no clause, and
     * only then applies minimum_should_match -- which it skips when there are
     * no should clauses. The filter has to say match_none itself.
     */
    expect($clause)->toHaveKey('match_none');
    expect($clause)->not->toHaveKey('bool');
    expect(json_encode($clause))->toBe('{"match_none":{}}');
});
