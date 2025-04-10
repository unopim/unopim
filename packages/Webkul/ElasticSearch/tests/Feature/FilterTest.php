<?php

use PHPUnit\Framework\ExpectationFailedException;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Attribute\Models\AttributeFamily;
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

it('should filter products by sku in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create([
        'sku' => 'product_sku_test',
    ]);

    $sku = 'product_sku_test';

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($sku) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('sku', $args['query']['term']);
            $this->assertEquals($sku, $args['query']['term']['sku']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits'  => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'sku' => $sku,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($product->sku, $response['hits']['hits'][0]['_source']['sku']);
});

it('should filter products by type (simple or configurable) in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create([
        'type' => 'simple',
    ]);

    $type = 'simple';

    config(['elasticsearch.enabled' => true]);

    $product->type = $type;

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($type) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('type', $args['query']['term']);
            $this->assertEquals($type, $args['query']['term']['type']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits'  => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'type' => $type,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($product->type, $response['hits']['hits'][0]['_source']['type']);
});

it('should filter products by status in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create([
        'values' => [
            'common' => [
                'status' => 'true',
            ]
        ],
    ]);

    $status = 'true';

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($status) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('values.common.status', $args['query']['term']);
            $this->assertEquals($status, $args['query']['term']['values.common.status']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'values.common.status' => $status,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($product->values['common']['status'], $response['hits']['hits'][0]['_source']['values']['common']['status']);
});

it('should filter product by id in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create();

    $productId = $product->id;

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($productId) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('id', $args['query']['term']);
            $this->assertEquals($productId, $args['query']['term']['id']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits'  => [
                    [
                        '_id'     => $productId,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'id' => $productId,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by parent_id in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $parentProduct = Product::factory()->create();

    $childProduct = Product::factory()->create([
        'parent_id' => $parentProduct->id,
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($parentProduct) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('parent_id', $args['query']['term']);
            $this->assertEquals($parentProduct->id, $args['query']['term']['parent_id']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id'     => $childProduct->id,
                        '_source' => $childProduct->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'parent_id' => $parentProduct->id,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($childProduct->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($parentProduct->id, $response['hits']['hits'][0]['_source']['parent_id']);
});

it('should filter products by created_at in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $createdAt = now()->subDays(5);
    $product = Product::factory()->create([
        'created_at' => $createdAt,
    ]);

    config(['elasticsearch.enabled' => true]);

    $filterDate = now()->subDays(10)->toISOString();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($filterDate) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('range', $args['query']);
            $this->assertArrayHasKey('created_at', $args['query']['range']);
            $this->assertArrayHasKey('gte', $args['query']['range']['created_at']);
            $this->assertEquals($filterDate, $args['query']['range']['created_at']['gte']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits'  => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'range' => [
                'created_at' => [
                    'gte' => $filterDate,
                ],
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($product->created_at->toISOString(), $response['hits']['hits'][0]['_source']['created_at']);
});

it('should filter products by updated_at in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $updatedAt = now()->subDays(2);
    $product = Product::factory()->create([
        'updated_at' => $updatedAt,
    ]);

    config(['elasticsearch.enabled' => true]);

    $filterDate = now()->subDays(5)->toISOString();

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($filterDate) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('range', $args['query']);
            $this->assertArrayHasKey('updated_at', $args['query']['range']);
            $this->assertArrayHasKey('gte', $args['query']['range']['updated_at']);
            $this->assertEquals($filterDate, $args['query']['range']['updated_at']['gte']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits'  => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'range' => [
                'updated_at' => [
                    'gte' => $filterDate,
                ],
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($product->updated_at->toISOString(), $response['hits']['hits'][0]['_source']['updated_at']);
});

it('should filter products by attribute_family_id in elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $attributeFamily = AttributeFamily::factory()->create();

    $product = Product::factory()->create([
        'attribute_family_id' => $attributeFamily->id,
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attributeFamily) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('attribute_family_id', $args['query']['term']);
            $this->assertEquals($attributeFamily->id, $args['query']['term']['attribute_family_id']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits'  => [
                    [
                        '_id'     => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'attribute_family_id' => $attributeFamily->id,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
    $this->assertEquals($attributeFamily->id, $response['hits']['hits'][0]['_source']['attribute_family_id']);
});

it('should filter products by text type attributes', function () {
    config(['elasticsearch.enabled' => false]);

    $textAttributes = ['name'];

    $product = Product::factory()->create();

    config(['elasticsearch.enabled' => true]);

    $searchTerm = 'Product Name';

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($textAttributes, $searchTerm) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('bool', $args['query']);
            $this->assertArrayHasKey('should', $args['query']['bool']);

            foreach ($textAttributes as $attribute) {
                $this->assertContains([
                    'match' => [
                        $attribute => $searchTerm,
                    ]
                ], $args['query']['bool']['should']);
            }

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ]
                ],
            ],
        ]);

    $shouldQueries = collect($textAttributes)->map(fn ($attribute) => [
        'match' => [
            $attribute => $searchTerm,
        ]
    ])->toArray();

    $response = ElasticSearch::search([
        'query' => [
            'bool' => [
                'should' => $shouldQueries,
                'minimum_should_match' => 1,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by boolean type attributes', function () {
    config(['elasticsearch.enabled' => false]);
    $booleanAttributes = ['status'];

    $product = Product::factory()->create();

    config(['elasticsearch.enabled' => true]);

    $searchTerm = true;

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($booleanAttributes, $searchTerm) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('bool', $args['query']);
            $this->assertArrayHasKey('filter', $args['query']['bool']);

            foreach ($booleanAttributes as $attribute) {
                $this->assertContains([
                    'term' => [
                        $attribute => $searchTerm,
                    ]
                ], $args['query']['bool']['filter']);
            }

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ]
                ],
            ],
        ]);

    $termQueries = collect($booleanAttributes)->map(fn ($attribute) => [
        'term' => [
            $attribute => $searchTerm,
        ]
    ])->toArray();

    $response = ElasticSearch::search([
        'query' => [
            'bool' => [
                'filter' => $termQueries,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by multi-select type attributes', function () {
    config(['elasticsearch.enabled' => false]);

    $multiSelectAttributes = ['colors'];

    $product = Product::factory()->create();

    config(['elasticsearch.enabled' => true]);

    $searchValues = ['Red', 'Blue'];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($multiSelectAttributes, $searchValues) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('bool', $args['query']);
            $this->assertArrayHasKey('filter', $args['query']['bool']);

            foreach ($multiSelectAttributes as $attribute) {
                $this->assertContains([
                    'terms' => [
                        $attribute => $searchValues,
                    ]
                ], $args['query']['bool']['filter']);
            }

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id'     => $product->id,
                        '_source' => $product->toArray(),
                    ]
                ],
            ],
        ]);

    $termFilters = collect($multiSelectAttributes)->map(fn ($attr) => [
        'terms' => [
            $attr => $searchValues,
        ],
    ])->toArray();

    $response = ElasticSearch::search([
        'query' => [
            'bool' => [
                'filter' => $termFilters,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by textarea type attributes', function () {
    config(['elasticsearch.enabled' => false]);

    $textareaAttributes = ['description'];

    $product = Product::factory()->create([
        'values' => [
            'description' => 'This is a detailed description of the product.'
        ]
    ]);

    config(['elasticsearch.enabled' => true]);

    $searchTerm = 'detailed description';

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($textareaAttributes, $searchTerm) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('bool', $args['query']);
            $this->assertArrayHasKey('should', $args['query']['bool']);

            foreach ($textareaAttributes as $attribute) {
                $this->assertContains([
                    'match' => [
                        $attribute => $searchTerm,
                    ]
                ], $args['query']['bool']['should']);
            }

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id'     => $product->id,
                        '_source' => $product->toArray(),
                    ]
                ],
            ],
        ]);

    $shouldQueries = collect($textareaAttributes)->map(fn ($attribute) => [
        'match' => [
            $attribute => $searchTerm,
        ]
    ])->toArray();

    $response = ElasticSearch::search([
        'query' => [
            'bool' => [
                'should' => $shouldQueries,
                'minimum_should_match' => 1,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by date type attributes', function () {
    config(['elasticsearch.enabled' => false]);

    $dateAttributes = ['created_at'];

    $product = Product::factory()->create([
        'created_at' => '2025-04-01 00:00:00',
    ]);

    config(['elasticsearch.enabled' => true]);

    $searchDate = '2025-04-01';

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($dateAttributes, $searchDate) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('bool', $args['query']);
            $this->assertArrayHasKey('filter', $args['query']['bool']);

            foreach ($dateAttributes as $attribute) {
                $this->assertContains([
                    'range' => [
                        $attribute => [
                            'gte' => $searchDate,
                        ]
                    ]
                ], $args['query']['bool']['filter']);
            }

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $filterQueries = collect($dateAttributes)->map(fn ($attribute) => [
        'range' => [
            $attribute => [
                'gte' => $searchDate,
            ]
        ]
    ])->toArray();

    $response = ElasticSearch::search([
        'query' => [
            'bool' => [
                'filter' => $filterQueries,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by price type attribute', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    'en_US' => [
                        'price' => [
                            'USD' => 17.2,
                        ]
                    ]
                ]
            ]
        ]
    ]);

    config(['elasticsearch.enabled' => true]);

    $nestedPath = 'channel_locale_specific.default.en_US.price';
    $field = 'channel_locale_specific.default.en_US.price.USD';
    $price = 17.2;

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($nestedPath, $field, $price) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('nested', $args['query']);

            $nestedQuery = $args['query']['nested'];

            $this->assertEquals($nestedPath, $nestedQuery['path']);
            $this->assertArrayHasKey('range', $nestedQuery['query']);
            $this->assertArrayHasKey($field, $nestedQuery['query']['range']);

            $this->assertEquals($price, $nestedQuery['query']['range'][$field]['gte']);
            $this->assertEquals($price, $nestedQuery['query']['range'][$field]['lte']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ]
                ]
            ]
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'nested' => [
                'path' => $nestedPath,
                'query' => [
                    'range' => [
                        $field => [
                            'gte' => $price,
                            'lte' => $price,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by datetime attribute', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->create([
        'values' => [
            'unactivated_at' => '2024-12-10 00:00:00',
        ],
    ]);

    config(['elasticsearch.enabled' => true]);

    $field = 'unactivated_at';
    $searchDate = '2024-12-10T00:00:00';

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($field, $searchDate) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('range', $args['query']);
            $this->assertArrayHasKey($field, $args['query']['range']);
            $this->assertEquals($searchDate, $args['query']['range'][$field]['gte']);
            $this->assertEquals($searchDate, $args['query']['range'][$field]['lte']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'range' => [
                $field => [
                    'gte' => $searchDate,
                    'lte' => $searchDate,
                ],
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by image attribute type', function () {
    config(['elasticsearch.enabled' => false]);

    $imageName = 'image.jpg';
    $product = Product::factory()->create([
        'values' => [
            'image' => [$imageName],
        ],
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($imageName) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('image', $args['query']['term']);
            $this->assertEquals($imageName, $args['query']['term']['image']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'image' => $imageName,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by gallery attribute type', function () {
    config(['elasticsearch.enabled' => false]);

    $imageName = 'image.jpg';
    $product = Product::factory()->create([
        'values' => [
            'gallery' => [
                'storage/di/image.jpg',
                'storage/di/another-image.jpg',
            ],
        ],
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($imageName) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('terms', $args['query']);
            $this->assertArrayHasKey('gallery', $args['query']['terms']);
            $this->assertContains($imageName, $args['query']['terms']['gallery']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'terms' => [
                'gallery' => [$imageName],
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by file attribute', function () {
    config(['elasticsearch.enabled' => false]);

    $filePath = 'storage/files/product_manual.pdf';
    $product = Product::factory()->create([
        'values' => [
            'file' => $filePath,
        ],
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($filePath) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('file', $args['query']['term']);
            $this->assertEquals($filePath, $args['query']['term']['file']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'file' => $filePath,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});

it('should filter products by checkbox attribute', function () {
    config(['elasticsearch.enabled' => false]);

    $checkboxValue = true;

    $product = Product::factory()->create([
        'values' => [
            'checkbox_field' => $checkboxValue,
        ],
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($checkboxValue) {
            $this->assertArrayHasKey('query', $args);
            $this->assertArrayHasKey('term', $args['query']);
            $this->assertArrayHasKey('checkbox_field', $args['query']['term']);
            $this->assertEquals($checkboxValue, $args['query']['term']['checkbox_field']);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => $product->id,
                        '_source' => $product->toArray(),
                    ],
                ],
            ],
        ]);

    $response = ElasticSearch::search([
        'query' => [
            'term' => [
                'checkbox_field' => $checkboxValue,
            ],
        ],
    ]);

    $this->assertEquals(1, $response['hits']['total']);
    $this->assertEquals($product->id, $response['hits']['hits'][0]['_id']);
});
