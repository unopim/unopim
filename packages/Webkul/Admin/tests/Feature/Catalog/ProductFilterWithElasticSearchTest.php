<?php

use Webkul\Core\Facades\ElasticSearch;

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

it('should return the product grid data when elastic is enabled with default sort order', function () {
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

it('should return the product data filtered by sku', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

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

it('should return the product data filtered by type', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

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
                                    'query_string' => [
                                        'default_field' => 'type',
                                        'query'         => 'simple',
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

it('should return the product data filtered by attribute family', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

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
                                    'term' => [
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
