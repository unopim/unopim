<?php

use PHPUnit\Framework\ExpectationFailedException;
use Webkul\Category\Models\Category;
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

it('should index category in elastic search', function () {
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

        $this->assertEquals('testing_categories', $args['body'][0]['index']['_index']);

        $this->assertArrayHasKey('id', $args['body'][1]);

        return is_array($args) && ! empty($args['body']);
    });

    Artisan::call('unopim:category:index');
});

it('should index the category to elastic when category is created', function () {
    $category = new Category;

    $category->forceFill(Category::factory()->definition());

    /** This is called after the category->save function is called so here category id is available */
    ElasticSearch::shouldReceive('index')
        ->once()
        ->withArgs(function ($args) use ($category) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('id', $args);
                $this->assertArrayHasKey('body', $args);

                $this->assertEquals('testing_categories', $args['index']);
                $this->assertEquals($category->id, $args['id']);
                $this->assertEquals($category->toArray(), $args['body']);
            } catch (ExpectationFailedException $e) {
                throw $e;
            }

            return is_array($args) && ! empty($args['body']);
        });

    $category->save();
});

it('should index the category to elastic when category is updated', function () {
    $category = Category::latest()->first();

    $category->code = 'root_test_______';

    ElasticSearch::shouldReceive('index')
        ->once()
        ->withArgs(function ($args) use ($category) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('id', $args);
                $this->assertArrayHasKey('body', $args);

                $this->assertEquals('testing_categories', $args['index']);

                $this->assertEquals($category->id, $args['id']);
                $this->assertEquals($category->toArray(), $args['body']);
            } catch (ExpectationFailedException $e) {
                $this->fail($e->getMessage());
            }

            return true;
        });

    $category->save();
});

it('should remove category from elastic when category is deleted', function () {
    $category = Category::latest()->first();

    ElasticSearch::shouldReceive('delete')
        ->once()
        ->withArgs(function ($args) use ($category) {
            try {
                $this->assertArrayHasKey('index', $args);
                $this->assertArrayHasKey('id', $args);

                $this->assertEquals('testing_categories', $args['index']);
                $this->assertEquals($category->id, $args['id']);
            } catch (ExpectationFailedException $e) {
                $this->fail($e->getMessage());
            }

            return true;
        });

    $category->delete();
});
