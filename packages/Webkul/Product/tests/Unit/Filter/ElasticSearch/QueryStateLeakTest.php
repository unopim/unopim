<?php

use Webkul\ElasticSearch\ElasticSearchQuery as RealElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;
use Webkul\Product\Filter\ElasticSearch\Property\IdFilter;

/**
 * Reproduces unopim/unopim#559: the `elastic-search-query` singleton accumulates
 * clauses across logical queries, so a long-running worker (Octane, queue:work)
 * grows one query until Elasticsearch rejects it with maxClauseCount.
 */
beforeEach(function () {
    config(['elasticsearch.enabled' => true]);

    app()->instance('elastic-search-query', new RealElasticSearchQuery);
});

function applyIdFilter(int $id): void
{
    $filter = new IdFilter;

    $filter->setQueryManager(new ElasticSearchQuery);

    $filter->applyPropertyFilter('product_id', FilterOperators::NOT_EQUAL, $id);
}

it('does not carry clauses from a previous query into the next one', function () {
    applyIdFilter(71063);

    $first = ElasticSearchQuery::build();

    expect($first['query']['constant_score']['filter']['bool']['must_not'])->toHaveCount(1);

    applyIdFilter(71065);

    $second = ElasticSearchQuery::build();

    expect($second['query']['constant_score']['filter']['bool']['must_not'])->toHaveCount(1);
});

it('does not grow the query unboundedly across many validations', function () {
    foreach (range(1, 50) as $id) {
        applyIdFilter($id);
    }

    $clauses = ElasticSearchQuery::build()['query']['constant_score']['filter']['bool']['must_not'];

    expect($clauses)->toHaveCount(1);
});
