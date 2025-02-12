<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Facades\ElasticSearch;

beforeEach(function () {
    config([
        'elasticsearch.enabled'                     => true,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'localhost:8ssss',
    ]);

    Mockery::close();
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

    ElasticSearch::shouldReceive('bulk')->withArgs(function ($args) {
        $this->assertIsArray($args);

        $this->assertArrayHasKey('body', $args);
        $this->assertNotEmpty($args['body']);

        $this->assertArrayHasKey('index', $args['body'][0]);

        $this->assertEquals('testing_categories', $args['body'][0]['index']['_index']);

        $this->assertArrayHasKey('id', $args['body'][1]);

        return is_array($args) && ! empty($args['body']);
    });

    Artisan::call('category:index');
});

it('should index the category to elastic when category is created', function () {
    $definition = Category::factory()->definition();

    $category = new Category();

    $category->forceFill($definition);

    $category->save();

    ElasticSearch::shouldReceive('index')->withArgs(function ($args) use ($category){
        $this->assertArrayHasKey('index', $args);
        $this->assertArrayHasKey('id', $args);
        $this->assertArrayHasKey('body', $args);

        $this->assertEquals('testing_categories', $args['index']);
        $this->assertEquals($category->id, $args['id']);
        $this->assertEquals($category->toArray(), $args['body']);

        dump($category);
        return is_array($args) && ! empty($args['body']);
    });
});

it('should index the category to elastic when category is updated', function () {
    $category = Category::first();

    $category->code = 'root_test';

    $category->save();

    $result = ElasticSearch::shouldReceive('index')->withArgs(function ($args) use ($category) {
        $this->assertArrayHasKey('index', $args);
        $this->assertArrayHasKey('id', $args);
        $this->assertArrayHasKey('body', $args);

        $this->assertEquals('testing_categories', $args['index']);
        $this->assertEquals($category->id, $args['id']);
        $this->assertEquals($category->toArray(), $args['body']);
        // dump($category, $args);
        return $args;
    });

    // dd($result);
});
