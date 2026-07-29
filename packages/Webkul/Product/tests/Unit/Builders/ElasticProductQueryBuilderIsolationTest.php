<?php

use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Builders\ElasticProductQueryBuilder;
use Webkul\Product\Filter\FilterManager;

beforeEach(function () {
    config(['elasticsearch.enabled' => true]);
});

it('owns isolated Elasticsearch state for every resolved product query builder', function () {
    $firstBuilder = resolve(ElasticProductQueryBuilder::class);
    $secondBuilder = resolve(ElasticProductQueryBuilder::class);

    $firstBuilder->applyFilter('product_id', FilterOperators::NOT_EQUAL, 101);
    $secondBuilder->applyFilter('product_id', FilterOperators::NOT_EQUAL, 202);

    $firstMustNot = $firstBuilder->build()['query']['constant_score']['filter']['bool']['must_not'];
    $secondMustNot = $secondBuilder->build()['query']['constant_score']['filter']['bool']['must_not'];

    expect($firstBuilder)->not->toBe($secondBuilder)
        ->and($firstMustNot)->toBe([['term' => ['id' => 101]]])
        ->and($secondMustNot)->toBe([['term' => ['id' => 202]]]);
});

it('keeps clause counts constant across repeated validation-sized operations', function () {
    foreach (range(1, 100) as $productId) {
        $queryBuilder = resolve(ElasticProductQueryBuilder::class);

        $queryBuilder->applyFilter('product_id', FilterOperators::NOT_EQUAL, $productId);

        $mustNot = $queryBuilder->build()['query']['constant_score']['filter']['bool']['must_not'];

        expect($mustNot)->toBe([['term' => ['id' => $productId]]]);
    }
});

it('preserves direct construction with the existing dependencies', function () {
    $queryBuilder = new ElasticProductQueryBuilder(
        resolve(AttributeService::class),
        resolve(FilterManager::class),
    );

    $queryBuilder->applyFilter('product_id', FilterOperators::NOT_EQUAL, 303);

    $mustNot = $queryBuilder->build()['query']['constant_score']['filter']['bool']['must_not'];

    expect($mustNot)->toBe([['term' => ['id' => 303]]]);
});
