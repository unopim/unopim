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
});

it('should return the product data', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

    $data = [
        [
            'pagination' => [
                'page'     => 1,
                'per_page' => 10,
            ],
        ],
    ];

    ElasticSearch::shouldReceive('search')->andReturn([
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

it('should return the product data filter by default fields sku, attribute family, status, type, name', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'filters' => [
            'sku'              => ['test-sku'],
            'attribute_family' => [1],
            'status'           => [1],
            'type'             => ['simple'],
            'name'             => ['Test Product'],
        ],
    ];

    ElasticSearch::shouldReceive('search')->andReturn([
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

it('should return the product data by dynamic columns', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'managedColumns' => ['sku', 'image', 'name', 'price'],
    ];

    ElasticSearch::shouldReceive('search')->andReturn([
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

it('should return the product data filter by attributes', function () {
    $this->loginAsAdmin();

    config(['elasticsearch.enabled' => true]);

    $data = [
        'pagination' => [
            'page'     => 1,
            'per_page' => 10,
        ],

        'managedColumns' => ['sku', 'image', 'name', 'price'],
        'filters'        => [
            'sku'   => ['test-sku'],
            'name'  => ['Test Product'],
            'price' => [
                ['USD', '67'],
            ],
            'color' => ['test-color'],
            'Date'  => [
                ['2025-03-17', '2025-03-23'],
            ],
        ],
    ];

    ElasticSearch::shouldReceive('search')->andReturn([
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
