<?php

use Webkul\ElasticSearch\ElasticSearchQuery as RealElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;
use Webkul\Product\Filter\ElasticSearch\Property\StatusFilter;

/**
 * Rebind the `elastic-search-query` container singleton to a fresh instance
 * before every test and return it, so each case inspects an isolated query
 * state. Production wires the singleton up the same way — see
 * ElasticSearchServiceProvider::registerFacades.
 */
function freshElasticQuery(): RealElasticSearchQuery
{
    $instance = new RealElasticSearchQuery;

    app()->instance('elastic-search-query', $instance);

    return $instance;
}

beforeEach(function () {
    config(['elasticsearch.enabled' => true]);
});

describe('StatusFilter coerces filter values to booleans for ES8 strict parsing', function () {

    it('coerces status=1 to boolean true in the terms clause', function () {
        $query = freshElasticQuery();

        $filter = new StatusFilter;
        $filter->setQueryManager(new ElasticSearchQuery);

        $filter->applyPropertyFilter('status', FilterOperators::IN, ['1']);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toHaveCount(1);
        expect($terms[0])->toBeBool();
        expect($terms[0])->toBeTrue();
    });

    it('coerces status=0 to boolean false in the terms clause', function () {
        $query = freshElasticQuery();

        $filter = new StatusFilter;
        $filter->setQueryManager(new ElasticSearchQuery);

        $filter->applyPropertyFilter('status', FilterOperators::IN, ['0']);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toHaveCount(1);
        expect($terms[0])->toBeBool();
        expect($terms[0])->toBeFalse();
    });

    it('coerces mixed "1"/"0" values to [true, false]', function () {
        $query = freshElasticQuery();

        $filter = new StatusFilter;
        $filter->setQueryManager(new ElasticSearchQuery);

        $filter->applyPropertyFilter('status', FilterOperators::IN, ['1', '0']);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toBe([true, false]);
    });

    it('passes through native booleans unchanged', function () {
        $query = freshElasticQuery();

        $filter = new StatusFilter;
        $filter->setQueryManager(new ElasticSearchQuery);

        $filter->applyPropertyFilter('status', FilterOperators::IN, [true, false]);

        $terms = $query->build()['query']['constant_score']['filter']['bool']['filter'][0]['terms']['status'];

        expect($terms)->toBe([true, false]);
    });
});
