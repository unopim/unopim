<?php

use Illuminate\Support\Facades\Facade;
use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\ElasticSearchQuery as ElasticSearchQueryAccumulator;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;
use Webkul\Product\Filter\ElasticSearch\BooleanFilter;
use Webkul\Product\Filter\ElasticSearch\DateFilter;
use Webkul\Product\Filter\ElasticSearch\DateTimeFilter;
use Webkul\Product\Filter\ElasticSearch\OptionFilter;
use Webkul\Product\Filter\ElasticSearch\PriceFilter;
use Webkul\Product\Filter\ElasticSearch\TextFilter;

beforeEach(function () {
    config(['elasticsearch.enabled' => true]);

    app()->instance('elastic-search-query', new ElasticSearchQueryAccumulator);

    Facade::clearResolvedInstance('elastic-search-query');
});

function esConditionAttribute(string $code, string $type): Attribute
{
    $attribute = new Attribute;

    $attribute->code = $code;
    $attribute->type = $type;
    $attribute->value_per_locale = false;
    $attribute->value_per_channel = false;

    return $attribute;
}

/**
 * Apply an Elasticsearch attribute filter against a real ElasticSearchQuery accumulator
 * and return the built query array.
 */
function applyElasticSearchFilter($filter, Attribute $attribute, FilterOperators $operator, $value): array
{
    $filter->setQueryManager(new ElasticSearchQuery);

    $filter->addAttributeFilter($attribute, $operator, $value, 'en_US', 'default');

    return ElasticSearchQuery::build();
}

/**
 * The accumulated `filter` (bool.filter) clauses of a built query.
 */
function esFilterClauses(array $build): array
{
    return $build['query']['constant_score']['filter']['bool']['filter'] ?? [];
}

/**
 * The accumulated `must_not` (bool.must_not) clauses of a built query.
 */
function esMustNotClauses(array $build): array
{
    return $build['query']['constant_score']['filter']['bool']['must_not'] ?? [];
}

describe('OptionFilter supports the option operators offered by the product datagrid', function () {
    $select = fn () => esConditionAttribute('color', Attribute::SELECT_FIELD_TYPE);
    $multiselect = fn () => esConditionAttribute('tags', Attribute::MULTISELECT_FIELD_TYPE);

    it('uses a terms query for a select in list', function () use ($select) {
        $build = applyElasticSearchFilter(new OptionFilter, $select(), FilterOperators::IN, ['red', 'blue']);

        expect(esFilterClauses($build))->toBe([
            ['terms' => ['values.common.color-select' => ['red', 'blue']]],
        ]);
    });

    it('uses capped wildcard clauses for a multiselect in list', function () use ($multiselect) {
        $build = applyElasticSearchFilter(new OptionFilter, $multiselect(), FilterOperators::IN, ['a', 'b']);

        expect(esFilterClauses($build))->toBe([
            [
                'bool' => [
                    'should' => [
                        ['wildcard' => ['values.common.tags-multiselect' => ['value' => '*a*', 'rewrite' => 'top_terms_1024']]],
                        ['wildcard' => ['values.common.tags-multiselect' => ['value' => '*b*', 'rewrite' => 'top_terms_1024']]],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
        ]);
    });

    it('negates the terms query for not in list', function () use ($select) {
        $build = applyElasticSearchFilter(new OptionFilter, $select(), FilterOperators::NOT_IN, ['red']);

        expect(esMustNotClauses($build))->toBe([
            ['terms' => ['values.common.color-select' => ['red']]],
        ]);
    });

    it('excludes documents having the field for is empty', function () use ($select) {
        $build = applyElasticSearchFilter(new OptionFilter, $select(), FilterOperators::IS_EMPTY, []);

        expect(esMustNotClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.color-select']],
        ]);
    });

    it('requires the field to exist for is not empty', function () use ($select) {
        $build = applyElasticSearchFilter(new OptionFilter, $select(), FilterOperators::IS_NOT_EMPTY, []);

        expect(esFilterClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.color-select']],
        ]);
    });
});

describe('TextFilter supports the text operators offered by the product datagrid', function () {
    $text = fn () => esConditionAttribute('description', Attribute::TEXT_TYPE);

    it('uses a terms query for in list', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::IN, ['foo', 'bar']);

        expect(esFilterClauses($build))->toBe([
            ['terms' => ['values.common.description-text' => ['foo', 'bar']]],
        ]);
    });

    it('uses a single match_phrase_prefix for a single contains value', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::CONTAINS, ['nike']);

        expect(esFilterClauses($build))->toBe([
            ['match_phrase_prefix' => ['values.common.description-text' => ['query' => 'nike', 'max_expansions' => 1000]]],
        ]);
    });

    it('wraps multiple contains values in a should bool', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::CONTAINS, ['nike', 'puma']);

        expect(esFilterClauses($build))->toBe([
            [
                'bool' => [
                    'should' => [
                        ['match_phrase_prefix' => ['values.common.description-text' => ['query' => 'nike', 'max_expansions' => 1000]]],
                        ['match_phrase_prefix' => ['values.common.description-text' => ['query' => 'puma', 'max_expansions' => 1000]]],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
        ]);
    });

    it('caps wildcard expansion on the keyword field for wildcard', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::WILDCARD, ['abc']);

        expect(esFilterClauses($build))->toBe([
            ['wildcard' => ['values.common.description-text.keyword' => ['value' => '*abc*', 'rewrite' => 'top_terms_1024']]],
        ]);
    });

    it('uses an exact term on the keyword field for equal', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::EQUAL, ['Shirt']);

        expect(esFilterClauses($build))->toBe([
            ['term' => ['values.common.description-text.keyword' => 'Shirt']],
        ]);
    });

    it('negates the keyword terms query for not in list', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::NOT_IN, ['Black']);

        expect(esMustNotClauses($build))->toBe([
            ['terms' => ['values.common.description-text.keyword' => ['Black']]],
        ]);
    });

    it('excludes documents having the field for is empty', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::IS_EMPTY, ['']);

        expect(esMustNotClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.description-text']],
        ]);
    });

    it('requires the field to exist for is not empty', function () use ($text) {
        $build = applyElasticSearchFilter(new TextFilter, $text(), FilterOperators::IS_NOT_EMPTY, ['']);

        expect(esFilterClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.description-text']],
        ]);
    });
});

describe('PriceFilter supports the price operators offered by the product datagrid', function () {
    $price = fn () => esConditionAttribute('price', Attribute::PRICE_FIELD_TYPE);

    it('uses an exact term scoped to the currency for equal', function () use ($price) {
        $build = applyElasticSearchFilter(new PriceFilter, $price(), FilterOperators::EQUAL, ['USD', '100']);

        expect(esFilterClauses($build))->toBe([
            ['term' => ['values.common.price-price.USD' => '100']],
        ]);
    });

    it('uses a gt range for greater than', function () use ($price) {
        $build = applyElasticSearchFilter(new PriceFilter, $price(), FilterOperators::GREATER_THAN, ['USD', '100']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.price-price.USD' => ['gt' => '100']]],
        ]);
    });

    it('uses an lt range for less than', function () use ($price) {
        $build = applyElasticSearchFilter(new PriceFilter, $price(), FilterOperators::LESS_THAN, ['USD', '50']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.price-price.USD' => ['lt' => '50']]],
        ]);
    });

    it('uses a gte/lte range for a range', function () use ($price) {
        $build = applyElasticSearchFilter(new PriceFilter, $price(), FilterOperators::RANGE, ['USD', '20', '50']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.price-price.USD' => ['gte' => '20', 'lte' => '50']]],
        ]);
    });

    it('excludes documents having the currency field for is empty', function () use ($price) {
        $build = applyElasticSearchFilter(new PriceFilter, $price(), FilterOperators::IS_EMPTY, ['USD', '']);

        expect(esMustNotClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.price-price.USD']],
        ]);
    });

    it('scopes the field to the chosen currency', function () use ($price) {
        $build = applyElasticSearchFilter(new PriceFilter, $price(), FilterOperators::GREATER_THAN, ['EUR', '10']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.price-price.EUR' => ['gt' => '10']]],
        ]);
    });
});

describe('DateFilter supports the date operators offered by the product datagrid', function () {
    $date = fn () => esConditionAttribute('launch_date', Attribute::DATE_FIELD_TYPE);

    it('uses an lt range for before (less than)', function () use ($date) {
        $build = applyElasticSearchFilter(new DateFilter, $date(), FilterOperators::LESS_THAN, ['2026-01-01']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.launch_date-date' => ['lt' => '2026-01-01']]],
        ]);
    });

    it('uses a gt range for after (greater than)', function () use ($date) {
        $build = applyElasticSearchFilter(new DateFilter, $date(), FilterOperators::GREATER_THAN, ['2026-01-01']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.launch_date-date' => ['gt' => '2026-01-01']]],
        ]);
    });

    it('formats both bounds with the date format for a range', function () use ($date) {
        $build = applyElasticSearchFilter(new DateFilter, $date(), FilterOperators::RANGE, ['2026-01-01', '2026-02-01']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.launch_date-date' => ['gte' => '2026-01-01', 'lte' => '2026-02-01']]],
        ]);
    });

    it('excludes documents having the field for is empty', function () use ($date) {
        $build = applyElasticSearchFilter(new DateFilter, $date(), FilterOperators::IS_EMPTY, []);

        expect(esMustNotClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.launch_date-date']],
        ]);
    });
});

describe('DateTimeFilter supports the datetime operators offered by the product datagrid', function () {
    $datetime = fn () => esConditionAttribute('published_at', Attribute::DATETIME_FIELD_TYPE);

    it('uses an lt range for before (less than)', function () use ($datetime) {
        $build = applyElasticSearchFilter(new DateTimeFilter, $datetime(), FilterOperators::LESS_THAN, ['2026-01-01 12:00:00']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.published_at-datetime' => ['lt' => '2026-01-01 12:00:00']]],
        ]);
    });

    it('uses a gt range for after (greater than)', function () use ($datetime) {
        $build = applyElasticSearchFilter(new DateTimeFilter, $datetime(), FilterOperators::GREATER_THAN, ['2026-01-01 12:00:00']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.published_at-datetime' => ['gt' => '2026-01-01 12:00:00']]],
        ]);
    });

    it('formats both bounds with the datetime format for a range', function () use ($datetime) {
        $build = applyElasticSearchFilter(new DateTimeFilter, $datetime(), FilterOperators::RANGE, ['2026-01-01 00:00:00', '2026-02-01 00:00:00']);

        expect(esFilterClauses($build))->toBe([
            ['range' => ['values.common.published_at-datetime' => ['gte' => '2026-01-01 00:00:00', 'lte' => '2026-02-01 00:00:00']]],
        ]);
    });

    it('excludes documents having the field for is empty', function () use ($datetime) {
        $build = applyElasticSearchFilter(new DateTimeFilter, $datetime(), FilterOperators::IS_EMPTY, []);

        expect(esMustNotClauses($build))->toBe([
            ['exists' => ['field' => 'values.common.published_at-datetime']],
        ]);
    });
});

describe('BooleanFilter supports the boolean operators offered by the product datagrid', function () {
    $boolean = fn () => esConditionAttribute('status', Attribute::BOOLEAN_FIELD_TYPE);

    it('casts a truthy value to boolean true in a terms query for equal', function () use ($boolean) {
        $build = applyElasticSearchFilter(new BooleanFilter, $boolean(), FilterOperators::EQUAL, ['1']);

        expect(esFilterClauses($build))->toBe([
            ['terms' => ['values.common.status-boolean' => [true]]],
        ]);
    });

    it('casts a falsy value to boolean false in a terms query for equal', function () use ($boolean) {
        $build = applyElasticSearchFilter(new BooleanFilter, $boolean(), FilterOperators::EQUAL, ['0']);

        expect(esFilterClauses($build))->toBe([
            ['terms' => ['values.common.status-boolean' => [false]]],
        ]);
    });
});
