<?php

use Webkul\ElasticSearch\Console\Command\ProductIndexer;

function productIndexSettings(): array
{
    $indexer = app(ProductIndexer::class);

    $method = (new ReflectionClass($indexer))->getMethod('getUnopimProductSetting');
    $method->setAccessible(true);

    return $method->invoke($indexer);
}

it('creates the product index with the configured field limit', function () {
    config(['elasticsearch.total_fields_limit' => 3000]);

    expect(productIndexSettings())
        ->toHaveKey('mapping.total_fields.limit', 3000);
});

it('never drops the field limit below the elasticsearch default', function () {
    config(['elasticsearch.total_fields_limit' => 100]);

    expect(productIndexSettings())
        ->toHaveKey('mapping.total_fields.limit', 1000);
});
