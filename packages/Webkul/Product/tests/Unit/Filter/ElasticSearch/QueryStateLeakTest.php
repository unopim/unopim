<?php

use Webkul\ElasticSearch\ElasticSearchQuery;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\ElasticSearch\Property\IdFilter;

/**
 * Reproduces unopim/unopim#559: a query object shared across logical queries keeps
 * accumulating clauses, so a long-running worker (Octane, queue:work) grows one
 * query until Elasticsearch rejects it with maxClauseCount. Each builder owns its
 * own instance instead of reaching through the container singleton.
 */
beforeEach(function () {
    config(['elasticsearch.enabled' => true]);
});

function applyIdFilter(int $id): ElasticSearchQuery
{
    $query = new ElasticSearchQuery;

    $filter = new IdFilter;

    $filter->setQueryManager($query);

    $filter->applyPropertyFilter('product_id', FilterOperators::NOT_EQUAL, $id);

    return $query;
}

it('does not carry clauses from a previous query into the next one', function () {
    $first = applyIdFilter(71063)->build();

    expect($first['query']['constant_score']['filter']['bool']['must_not'])->toHaveCount(1);

    $second = applyIdFilter(71065)->build();

    expect($second['query']['constant_score']['filter']['bool']['must_not'])->toHaveCount(1);
});

it('does not grow the query unboundedly across many validations', function () {
    $clauseCounts = [];

    foreach (range(1, 50) as $id) {
        $clauseCounts[] = count(
            applyIdFilter($id)->build()['query']['constant_score']['filter']['bool']['must_not']
        );
    }

    expect(array_unique($clauseCounts))->toBe([1]);
});

it('refuses a query manager that is not an elasticsearch query', function () {
    $filter = new IdFilter;

    expect(fn () => $filter->setQueryManager(new stdClass))
        ->toThrow(InvalidArgumentException::class);
});
