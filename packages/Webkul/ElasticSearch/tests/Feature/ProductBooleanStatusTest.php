<?php

use PHPUnit\Framework\ExpectationFailedException;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config([
        'elasticsearch.enabled'                      => true,
        'elasticsearch.prefix'                       => 'testing',
        'elasticsearch.connection'                   => 'default',
        'elasticsearch.connections.default.hosts.0'  => 'testhost:9200',
    ]);

    $elasticClientMock = Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient');

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn($elasticClientMock)
        ->zeroOrMoreTimes();
});

describe('Product Observer indexes boolean status for ES8 compatibility (Issue #243)', function () {

    it('indexes product status as boolean true on update', function () {
        config(['elasticsearch.enabled' => false]);

        $product = Product::factory()->withInitialValues()->create(['status' => 0]);

        config(['elasticsearch.enabled' => true]);

        $product->status = 1;

        ElasticSearch::shouldReceive('index')
            ->once()
            ->withArgs(function ($args) {
                try {
                    $this->assertArrayHasKey('body', $args);
                    $status = $args['body']['status'];

                    // CRITICAL: status must be boolean, not integer
                    $this->assertIsBool($status, 'Product status sent to ES must be boolean, got: '.gettype($status).' value: '.var_export($status, true));
                    $this->assertTrue($status);
                } catch (ExpectationFailedException $e) {
                    throw $e;
                }

                return true;
            });

        $product->save();
    });

    it('indexes product status as boolean false on update', function () {
        config(['elasticsearch.enabled' => false]);

        $product = Product::factory()->withInitialValues()->create(['status' => 1]);

        config(['elasticsearch.enabled' => true]);

        $product->status = 0;

        ElasticSearch::shouldReceive('index')
            ->once()
            ->withArgs(function ($args) {
                try {
                    $this->assertArrayHasKey('body', $args);
                    $status = $args['body']['status'];

                    $this->assertIsBool($status, 'Product status sent to ES must be boolean, got: '.gettype($status).' value: '.var_export($status, true));
                    $this->assertFalse($status);
                } catch (ExpectationFailedException $e) {
                    throw $e;
                }

                return true;
            });

        $product->save();
    });

    it('indexes attribute_family status as boolean on update', function () {
        config(['elasticsearch.enabled' => false]);

        $product = Product::factory()->withInitialValues()->create();

        config(['elasticsearch.enabled' => true]);

        $product->status = 0;

        ElasticSearch::shouldReceive('index')
            ->once()
            ->withArgs(function ($args) {
                try {
                    $this->assertArrayHasKey('body', $args);

                    if (isset($args['body']['attribute_family']['status'])) {
                        $familyStatus = $args['body']['attribute_family']['status'];
                        $this->assertIsBool($familyStatus, 'attribute_family.status sent to ES must be boolean, got: '.gettype($familyStatus));
                    }
                } catch (ExpectationFailedException $e) {
                    throw $e;
                }

                return true;
            });

        $product->save();
    });
});

describe('ProductIndexer maps status as boolean type for ES8 (Issue #243)', function () {

    it('uses boolean type for status field mapping regardless of database driver', function () {
        config(['elasticsearch.enabled' => false]);

        Product::factory()->create();

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
                    $this->assertArrayHasKey('body', $args);
                    $this->assertArrayHasKey('mappings', $args['body']);

                    $properties = $args['body']['mappings']['properties'];

                    // Top-level status must be boolean
                    $this->assertEquals(['type' => 'boolean'], $properties['status']);

                    // attribute_family.status must also be boolean
                    $this->assertEquals(
                        ['type' => 'boolean'],
                        $properties['attribute_family']['properties']['status']
                    );
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

    it('casts status to boolean during bulk indexing', function () {
        config(['elasticsearch.enabled' => false]);

        Product::factory()->create(['status' => 1]);

        config(['elasticsearch.enabled' => true]);

        $indicesMock = Mockery::mock('Elastic\Elasticsearch\Endpoints\Indices');
        ElasticSearch::shouldReceive('indices')->andReturn($indicesMock)->between(1, 5);

        $indicesMockResponse = Mockery::mock('Elastic\Elasticsearch\Response\Elasticsearch');
        $indicesMock->shouldReceive('exists')->andReturn($indicesMockResponse);
        $indicesMockResponse->shouldReceive('asBool')->andReturn(true);

        ElasticSearch::shouldReceive('search')->andReturn([
            'hits' => [
                'total' => 0,
                'hits'  => [],
            ],
            '_scroll_id' => 'test_scroll',
        ])->zeroOrMoreTimes();

        ElasticSearch::shouldReceive('scroll')->andReturn([
            'hits' => [
                'hits' => [],
            ],
        ])->zeroOrMoreTimes();

        ElasticSearch::shouldReceive('bulk')
            ->between(1, 10000)
            ->withArgs(function ($args) {
                $this->assertIsArray($args);
                $this->assertArrayHasKey('body', $args);

                // The product body is at index 1 (index 0 is the index action)
                if (isset($args['body'][1])) {
                    $productBody = $args['body'][1];

                    if (isset($productBody['status'])) {
                        $this->assertIsBool(
                            $productBody['status'],
                            'Bulk indexed product status must be boolean, got: '.gettype($productBody['status'])
                        );
                    }
                }

                return true;
            });

        Artisan::call('unopim:product:index');
    });
});
