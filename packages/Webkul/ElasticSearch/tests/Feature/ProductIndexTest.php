<?php

use PHPUnit\Framework\ExpectationFailedException;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config([
        'elasticsearch.enabled'                     => true,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
    ]);

    $elasticClientMock = Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient');

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn($elasticClientMock);
});

it('should index product in elastic search', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create();

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')->andReturn([
        'hits' => [
            'total' => 0,
            'hits'  => [],
        ],
        '_scroll_id' => '83h84747',
    ]);

    ElasticSearch::shouldReceive('scroll')->andReturn([
        'hits' => [
            'hits' => [],
        ],
    ]);

    $indicesMock = Mockery::mock('Elastic\Elasticsearch\Endpoints\Indices');

    ElasticSearch::shouldReceive('indices')->andReturn($indicesMock);

    $indexPrefix = config('elasticsearch.prefix');

    $productIndex = strtolower($indexPrefix.'_products');

    $indicesMockResponse = Mockery::mock('Elastic\Elasticsearch\Response\Elasticsearch');

    $indicesMock->shouldReceive('exists')->with([
        'index' => $productIndex,
    ])->andReturn($indicesMockResponse);

    $indicesMockResponse->shouldReceive('asBool')->andReturn(true);

    ElasticSearch::shouldReceive('bulk')->between(1, 10000)->withArgs(function ($args) {
        $this->assertIsArray($args);

        $this->assertArrayHasKey('body', $args);
        $this->assertNotEmpty($args['body']);

        $this->assertArrayHasKey('index', $args['body'][0]);

        $this->assertEquals('testing_products', $args['body'][0]['index']['_index']);

        $this->assertArrayHasKey('id', $args['body'][1]);

        return is_array($args) && ! empty($args['body']);
    });

    Artisan::call('unopim:product:index');
});

it('should index the product to elastic when product is created', function () {
    $product = new Product;

    $product->forceFill(Product::factory()->definition());

    $product->values = [];

    /** This is called after the product->save function is called so here product id is available */
    ElasticSearch::shouldReceive('index')
        ->once()
        ->withArgs(function ($args) use ($product) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('id', $args);
                $this->assertArrayHasKey('body', $args);

                $this->assertEquals('testing_products', $args['index']);
                $this->assertEquals($product->id, $args['id']);
                $this->assertEquals($product->toArray(), $args['body']);
            } catch (ExpectationFailedException $e) {
                throw $e;
            }

            return is_array($args) && ! empty($args['body']);
        });

    $product->save();
});

it('should index the product to elastic when product is updated', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->withInitialValues()->create();

    config(['elasticsearch.enabled' => true]);

    $product->status = 0;

    ElasticSearch::shouldReceive('index')
        ->once()
        ->withArgs(function ($args) use ($product) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('id', $args);
                $this->assertArrayHasKey('body', $args);

                $this->assertEquals('testing_products', $args['index']);

                $this->assertEquals($product->id, $args['id']);

                $productArray = $product->toArray();

                // According to indexing format we need to change the sku key to sku-text according to normalizer
                $productArray['values']['common']['sku-text'] = $product->sku;
                unset($productArray['values']['common']['sku']);

                $this->assertEquals($productArray, $args['body']);
            } catch (ExpectationFailedException $e) {
                $this->fail($e->getMessage());
            }

            return true;
        });

    $product->save();
});

it('should remove product from elastic when product is deleted', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create();

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('delete')
        ->once()
        ->withArgs(function ($args) use ($product) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('id', $args);

                $this->assertEquals('testing_products', $args['index']);
                $this->assertEquals($product->id, $args['id']);
            } catch (ExpectationFailedException $e) {
                $this->fail($e->getMessage());
            }

            return true;
        });

    $product->delete();
});

it('should create dynamic mapping templates for product attributes', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create();

    config(['elasticsearch.enabled' => true]);

    $indicesMock = Mockery::mock('Elastic\Elasticsearch\Endpoints\Indices');

    ElasticSearch::shouldReceive('indices')->andReturn($indicesMock)->between(1, 5);

    $indicesMockResponse = Mockery::mock('Elastic\Elasticsearch\Response\Elasticsearch');

    $indicesMock->shouldReceive('exists')->andReturn($indicesMockResponse);
    $indicesMockResponse->shouldReceive('asBool')->andReturn(false);

    $indicesMock->shouldReceive('create')
        ->once()
        ->withArgs(function ($args) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('body', $args);

                $this->assertArrayHasKey('mappings', $args['body']);
                $this->assertArrayHasKey('dynamic_templates', $args['body']['mappings']);

                $expectedDynamicTemplates = [
                    [
                        'object_fields_common' => [
                            'path_match'         => 'values.common.*',
                            'match_mapping_type' => 'object',
                            'mapping'            => ['type' => 'object'],
                        ],
                    ],
                    [
                        'object_fields_locale_specific' => [
                            'path_match'         => 'values.locale_specific.*.*',
                            'match_mapping_type' => 'object',
                            'mapping'            => ['type' => 'object'],
                        ],
                    ],
                    [
                        'object_fields_channel_specific' => [
                            'path_match'         => 'values.channel_specific.*.*',
                            'match_mapping_type' => 'object',
                            'mapping'            => ['type' => 'object'],
                        ],
                    ],
                    [
                        'object_fields_channel_locale_specific' => [
                            'path_match'         => 'values.channel_locale_specific.*.*.*',
                            'match_mapping_type' => 'object',
                            'mapping'            => ['type' => 'object'],
                        ],
                    ],
                    // Text Fields
                    [
                        'text_fields_common' => [
                            'path_match' => 'values.common.*-text',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'text_fields_locale_specific' => [
                            'path_match' => 'values.locale_specific.*.*-text',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'text_fields_channel_specific' => [
                            'path_match' => 'values.channel_specific.*.*-text',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'text_fields_channel_locale_specific' => [
                            'path_match' => 'values.channel_locale_specific.*.*.*-text',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Textarea Fields
                    [
                        'textarea_fields_common' => [
                            'path_match' => 'values.common.*-textarea',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'textarea_fields_locale_specific' => [
                            'path_match' => 'values.locale_specific.*.*-textarea',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'textarea_fields_channel_specific' => [
                            'path_match' => 'values.channel_specific.*.*-textarea',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'textarea_fields_channel_locale_specific' => [
                            'path_match' => 'values.channel_locale_specific.*.*.*-textarea',
                            'mapping'    => [
                                'type'   => 'text',
                                'fields' => [
                                    'keyword' => [
                                        'type'       => 'keyword',
                                        'normalizer' => 'string_normalizer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    // Price Fields
                    [
                        'price_fields_common' => [
                            'path_match' => 'values.common.*-price.*',
                            'mapping'    => ['type' => 'float'],
                        ],
                    ],
                    [
                        'price_fields_locale_specific' => [
                            'path_match' => 'values.locale_specific.*.*-price.*',
                            'mapping'    => ['type' => 'float'],
                        ],
                    ],
                    [
                        'price_fields_channel_specific' => [
                            'path_match' => 'values.channel_specific.*.*-price.*',
                            'mapping'    => ['type' => 'float'],
                        ],
                    ],
                    [
                        'price_fields_channel_locale_specific' => [
                            'path_match' => 'values.channel_locale_specific.*.*.*-price.*',
                            'mapping'    => ['type' => 'float'],
                        ],
                    ],
                    // Datetime Fields
                    [
                        'datetime_fields_common' => [
                            'path_match' => 'values.common.*-datetime',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd HH:mm:ss',
                            ],
                        ],
                    ],
                    [
                        'datetime_fields_locale_specific' => [
                            'path_match' => 'values.locale_specific.*.*-datetime',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd HH:mm:ss',
                            ],
                        ],
                    ],
                    [
                        'datetime_fields_channel_specific' => [
                            'path_match' => 'values.channel_specific.*.*-datetime',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd HH:mm:ss',
                            ],
                        ],
                    ],
                    [
                        'datetime_fields_channel_locale_specific' => [
                            'path_match' => 'values.channel_locale_specific.*.*.*-datetime',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd HH:mm:ss',
                            ],
                        ],
                    ],
                    // Date Fields
                    [
                        'date_fields_common' => [
                            'path_match' => 'values.common.*-date',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd',
                            ],
                        ],
                    ],
                    [
                        'date_fields_locale_specific' => [
                            'path_match' => 'values.locale_specific.*.*-date',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd',
                            ],
                        ],
                    ],
                    [
                        'date_fields_channel_specific' => [
                            'path_match' => 'values.channel_specific.*.*-date',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd',
                            ],
                        ],
                    ],
                    [
                        'date_fields_channel_locale_specific' => [
                            'path_match' => 'values.channel_locale_specific.*.*.*-date',
                            'mapping'    => [
                                'type'   => 'date',
                                'format' => 'yyyy-MM-dd',
                            ],
                        ],
                    ],
                    // Fallbacks for string fields
                    [
                        'fallback_fields_common' => [
                            'path_match'         => 'values.common.*',
                            'match_mapping_type' => 'string',
                            'mapping'            => ['type' => 'keyword'],
                        ],
                    ],
                    [
                        'fallback_fields_locale_specific' => [
                            'path_match'         => 'values.locale_specific.*.*',
                            'match_mapping_type' => 'string',
                            'mapping'            => ['type' => 'keyword'],
                        ],
                    ],
                    [
                        'fallback_fields_channel_specific' => [
                            'path_match'         => 'values.channel_specific.*.*',
                            'match_mapping_type' => 'string',
                            'mapping'            => ['type' => 'keyword'],
                        ],
                    ],
                    [
                        'fallback_fields_channel_locale_specific' => [
                            'path_match'         => 'values.channel_locale_specific.*.*.*',
                            'match_mapping_type' => 'string',
                            'mapping'            => ['type' => 'keyword'],
                        ],
                    ],
                    // Final fallback for any object type
                    [
                        'fallback_object' => [
                            'path_match'         => 'values.*',
                            'match_mapping_type' => 'object',
                            'mapping'            => ['type' => 'object'],
                        ],
                    ],
                ];

                $this->assertEquals($expectedDynamicTemplates, $args['body']['mappings']['dynamic_templates']);
            } catch (ExpectationFailedException $e) {
                $this->fail($e->getMessage());
            }

            return true;
        });

    ElasticSearch::shouldReceive('search')->andReturn([
        'hits' => [
            'total' => 0,
            'hits'  => [],
        ],
        '_scroll_id' => '83h84747',
    ])->zeroOrMoreTimes();

    ElasticSearch::shouldReceive('scroll')->andReturn([
        'hits' => [
            'hits' => [],
        ],
    ])->zeroOrMoreTimes();

    ElasticSearch::shouldReceive('bulk')->zeroOrMoreTimes();

    Artisan::call('unopim:product:index');
});
