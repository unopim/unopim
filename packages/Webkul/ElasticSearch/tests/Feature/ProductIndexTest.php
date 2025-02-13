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

    ElasticSearch::shouldReceive('bulk')->between(1, 10000)->withArgs(function ($args) {
        $this->assertIsArray($args);

        $this->assertArrayHasKey('body', $args);
        $this->assertNotEmpty($args['body']);

        $this->assertArrayHasKey('index', $args['body'][0]);

        $this->assertEquals('testing_products', $args['body'][0]['index']['_index']);

        $this->assertArrayHasKey('id', $args['body'][1]);

        return is_array($args) && ! empty($args['body']);
    });

    Artisan::call('product:index');
});

it('should index the product to elastic when product is created', function () {
    $product = new Product;

    $product->forceFill(Product::factory()->definition());

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
    $product = Product::latest()->first();

    $product->sku = 'product_sku_test_____';

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
                $this->fail($e->getMessage());
            }

            return true;
        });

    $product->save();
});

it('should remove product from elastic when product is deleted', function () {
    $product = Product::latest()->first();

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
