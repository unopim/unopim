<?php

use Webkul\Product\Filter\Database\TextFilter as DatabaseTextFilter;
use Webkul\Product\Filter\ElasticSearch\TextFilter as ElasticSearchTextFilter;
use Webkul\Product\Filter\FilterManager;

it('resolves the filters of the configured engine by default', function () {
    config(['elasticsearch.enabled' => false]);

    expect(app(FilterManager::class)->getAttributeFilters()->first())->toBeInstanceOf(DatabaseTextFilter::class);
});

it('resolves the filters of an explicitly requested engine', function () {
    config(['elasticsearch.enabled' => false]);

    $elasticsearch = app()->make(FilterManager::class, ['elasticsearch' => true]);

    expect($elasticsearch->getAttributeFilters()->first())->toBeInstanceOf(ElasticSearchTextFilter::class);

    config(['elasticsearch.enabled' => true]);

    $database = app()->make(FilterManager::class, ['elasticsearch' => false]);

    expect($database->getAttributeFilters()->first())->toBeInstanceOf(DatabaseTextFilter::class);
});
