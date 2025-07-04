<?php

use Webkul\Attribute\Models\Attribute;
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

    $this->loginAsAdmin();
});

it('should return the product grid data with default sort order when Elasticsearch is enabled', function () {
    $data = [
        [
            'pagination' => [
                'page'     => 1,
                'per_page' => 10,
            ],
        ],
    ];

    $sortData = [
        'updated_at' => [
            'order'         => 'desc',
            'missing'       => '_last',
            'unmapped_type' => 'keyword',
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($sortData) {
            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('sort');
            expect($args['body']['sort'])->toBeArray();
            expect($args['body']['sort'])->toEqual($sortData);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by SKU using Elasticsearch', function () {
    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'filters' => [
            'sku' => ['testSku123'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'query_string' => [
                                        'default_field' => 'sku',
                                        'query'         => '*testSku123*',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by type using Elasticsearch', function () {
    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'filters' => [
            'type' => ['simple'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'terms' => [
                                        'type' => ['simple'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by attribute family using Elasticsearch', function () {
    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'filters' => [
            'attribute_family' => [1],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'terms' => [
                                        'attribute_family_id' => [1],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by ID using Elasticsearch', function () {
    $data = [
        'managedColumns' => ['product_id'],

        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'filters' => [
            'product_id' => [
                11,
            ],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'terms' => [
                                        'id' => [11],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by parent using Elasticsearch', function () {
    config(['elasticsearch.enabled' => false]);

    $productId = Product::factory()->configurable()->withVariantProduct()->create([
        'sku' => 'test-sku',
    ])?->id;

    config(['elasticsearch.enabled' => true]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'managedColumns' => ['parent'],

        'filters' => [
            'parent' => ['test-sku'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($productId) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'terms' => [
                                        'parent_id' => [$productId],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by created_at using Elasticsearch', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => ['created_at'],

        'filters' => [
            'created_at' => [['2025-01-01', '2025-12-31']],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'range' => [
                                        'created_at' => [
                                            'gte' => '2025-01-01T00:00:01+00:00',
                                            'lte' => '2025-12-31T23:59:59+00:00',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by updated_at using Elasticsearch', function () {
    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => ['updated_at'],

        'filters' => [
            'updated_at' => [['2025-01-01', '2025-12-31']],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'range' => [
                                        'updated_at' => [
                                            'gte' => '2025-01-01T00:00:01+00:00',
                                            'lte' => '2025-12-31T23:59:59+00:00',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by text attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'text_attribute',
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['test_value', 'test_value2'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'query_string' => [
                                        'default_field' => 'values.common.'.$attribute->code.'-'.$attribute->type,
                                        'query'         => '"*test_value*" OR "*test_value2*"',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by textarea attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'textarea_attribute',
        'type'              => 'textarea',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['test_value'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'query_string' => [
                                        'default_field' => 'values.common.'.$attribute->code.'-'.$attribute->type,
                                        'query'         => '"*test_value*"',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by price attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'price_attribute',
        'type'              => 'price',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => [['USD', '67']],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'term' => [
                                        'values.common.'.$attribute->code.'-'.$attribute->type.'.USD' => '67',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by boolean attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'boolean_attribute',
        'type'              => 'boolean',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => [true],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'terms' => [
                                        'values.common.'.$attribute->code.'-'.$attribute->type => [true],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by select attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'select_attribute',
        'type'              => 'select',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['option1'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'query_string' => [
                                        'default_field' => 'values.common.'.$attribute->code.'-'.$attribute->type,
                                        'query'         => 'option1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by multiselect attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'multiselect_attribute',
        'type'              => 'multiselect',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['option1', 'option2'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'query_string' => [
                                        'default_field' => 'values.common.'.$attribute->code.'-'.$attribute->type,
                                        'query'         => '*option1* OR *option2*',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});
it('should filter products by date attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'datetime_attribute',
        'type'              => 'date',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => [['2025-01-01', '2025-12-31']],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'range' => [
                                        'values.common.'.$attribute->code.'-'.$attribute->type => [
                                            'gte' => '2025-01-01',
                                            'lte' => '2025-12-31',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by datetime attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'datetime_attribute',
        'type'              => 'datetime',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => [['2025-01-01 00:00:00', '2025-12-31 23:59:59']],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'range' => [
                                        'values.common.'.$attribute->code.'-'.$attribute->type => [
                                            'gte' => '2025-01-01 00:00:00',
                                            'lte' => '2025-12-31 23:59:59',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by image attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'image_attribute',
        'type'              => 'image',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['image1.jpg'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'wildcard' => [
                                                    'values.common.'.$attribute->code.'-'.$attribute->type => '*image1.jpg*',
                                                ],
                                            ],
                                        ],
                                        'minimum_should_match' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by file attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'file_attribute',
        'type'              => 'file',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['file1.pdf'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'wildcard' => [
                                                    'values.common.'.$attribute->code.'-'.$attribute->type => '*file1.pdf*',
                                                ],
                                            ],
                                        ],
                                        'minimum_should_match' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args)->toBeArray();
            expect($args)->toHaveKey('index');
            expect($args)->toHaveKey('body');
            expect($args['body'])->toHaveKey('query');
            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by checkbox attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'checkbox_attribute',
        'type'              => 'checkbox',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['option1', 'option2'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'query_string' => [
                                        'default_field' => 'values.common.'.$attribute->code.'-'.$attribute->type,
                                        'query'         => '*option1* OR *option2*',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});

it('should filter products by gallery attribute using Elasticsearch', function () {
    $attribute = Attribute::factory()->create([
        'code'              => 'gallery_attribute',
        'type'              => 'gallery',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => ['image1.jpg', 'image2.jpg'],
        ],
    ];

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) use ($attribute) {
            $expectedQuery = [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'bool' => [
                                        'should' => [
                                            [
                                                'wildcard' => [
                                                    'values.common.'.$attribute->code.'-'.$attribute->type => '*image1.jpg*',
                                                ],
                                            ], [
                                                'wildcard' => [
                                                    'values.common.'.$attribute->code.'-'.$attribute->type => '*image2.jpg*',
                                                ],
                                            ],
                                        ],
                                        'minimum_should_match' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            expect($args['body']['query'])->toEqual($expectedQuery);

            return true;
        })
        ->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => '83h84747',
        ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.products.index'), $data);

    $response->assertOk();
});
