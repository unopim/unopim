<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Models\Product;

/*
 * The filter UI offers "is empty" / "is not empty" for image, gallery and file
 * attributes, so both backends have to honour them. Elasticsearch previously
 * allowed only IN and CONTAINS for those types, which made the query builder
 * throw and the grid fall back to a full database scan.
 */

function mediaAttribute(string $type): Attribute
{
    return Attribute::factory()->create([
        'code'              => $type.'_emptiness_attribute',
        'type'              => $type,
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
    ]);
}

function mediaFilterRequest(Attribute $attribute, string $operator): array
{
    return [
        'pagination'     => ['page' => 1, 'per_page' => 10],
        'managedColumns' => [$attribute->code],
        'filters'        => [
            $attribute->code => [['operator' => $operator, 'value' => '']],
        ],
    ];
}

describe('elasticsearch', function () {
    beforeEach(function () {
        config([
            'elasticsearch.enabled'                     => true,
            'elasticsearch.prefix'                      => 'testing',
            'elasticsearch.connection'                  => 'default',
            'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
        ]);

        ElasticSearch::shouldReceive('makeConnection')
            ->andReturn(Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient'));

        $this->loginAsAdmin();
    });

    it('translates is empty into a negated exists clause', function (string $type) {
        config(['elasticsearch.enabled' => false]);
        $attribute = mediaAttribute($type);
        config(['elasticsearch.enabled' => true]);

        $path = 'values.common.'.$attribute->code.'-'.$type;

        ElasticSearch::shouldReceive('search')
            ->once()
            ->withArgs(function ($args) use ($path) {
                $clauses = $args['body']['query']['constant_score']['filter']['bool']['must_not'] ?? [];

                expect($clauses)->toContain(['exists' => ['field' => $path]]);

                return true;
            })
            ->andReturn(['hits' => ['hits' => [], 'total' => ['value' => 0]]]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), mediaFilterRequest($attribute, 'blank'))
            ->assertOk();
    })->with(['image', 'gallery', 'file']);

    it('translates is not empty into an exists clause', function (string $type) {
        config(['elasticsearch.enabled' => false]);
        $attribute = mediaAttribute($type);
        config(['elasticsearch.enabled' => true]);

        $path = 'values.common.'.$attribute->code.'-'.$type;

        ElasticSearch::shouldReceive('search')
            ->once()
            ->withArgs(function ($args) use ($path) {
                $filters = $args['body']['query']['constant_score']['filter']['bool']['filter'] ?? [];

                expect($filters)->toContain(['exists' => ['field' => $path]]);

                return true;
            })
            ->andReturn(['hits' => ['hits' => [], 'total' => ['value' => 0]]]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), mediaFilterRequest($attribute, 'not_blank'))
            ->assertOk();
    })->with(['image', 'gallery', 'file']);

    it('translates equals into a keyword term clause', function () {
        config(['elasticsearch.enabled' => false]);
        $attribute = mediaAttribute('image');
        config(['elasticsearch.enabled' => true]);

        $path = 'values.common.'.$attribute->code.'-image';

        ElasticSearch::shouldReceive('search')
            ->once()
            ->withArgs(function ($args) use ($path) {
                $clauses = $args['body']['query']['constant_score']['filter']['bool']['filter'] ?? [];

                expect($clauses)->toContain(['term' => [$path.'.keyword' => 'catalog/1/photo.jpg']]);

                return true;
            })
            ->andReturn(['hits' => ['hits' => [], 'total' => ['value' => 0]]]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), [
                'pagination'     => ['page' => 1, 'per_page' => 10],
                'managedColumns' => [$attribute->code],
                'filters'        => [
                    $attribute->code => [['operator' => 'eq', 'value' => 'catalog/1/photo.jpg']],
                ],
            ])->assertOk();
    });

    it('falls back to the database instead of erroring when a mass action query fails', function () {
        config(['elasticsearch.enabled' => false]);
        $product = Product::factory()->create(['sku' => 'mass-action-fallback']);
        config(['elasticsearch.enabled' => true]);

        ElasticSearch::shouldReceive('search')
            ->andThrow(new RuntimeException('elasticsearch down'));

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), [
                'pagination'      => ['page' => 1, 'per_page' => 10],
                'mass_action_ids' => true,
            ])->assertOk();

        expect($response->json('ids'))->toContain($product->id);
    });
});

describe('database', function () {
    beforeEach(function () {
        config(['elasticsearch.enabled' => false]);

        $this->loginAsAdmin();
    });

    it('separates products with and without a stored media value', function (string $type) {
        $attribute = mediaAttribute($type);

        $withValue = Product::factory()->create(['sku' => 'has-'.$type]);
        $withValue->values = ['common' => [$attribute->code => 'catalog/1/photo.jpg']];
        $withValue->save();

        $withoutValue = Product::factory()->create(['sku' => 'lacks-'.$type]);
        $withoutValue->values = ['common' => []];
        $withoutValue->save();

        $skusFor = function (string $operator) use ($attribute) {
            $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->json('GET', route('admin.catalog.products.index'), [
                    'pagination'     => ['page' => 1, 'per_page' => 50],
                    'managedColumns' => ['sku', $attribute->code],
                    'filters'        => [
                        $attribute->code => [['operator' => $operator, 'value' => '']],
                    ],
                ])->assertOk();

            return collect($response->json('records'))->pluck('sku')->all();
        };

        expect($skusFor('not_blank'))->toContain('has-'.$type)->not->toContain('lacks-'.$type);
        expect($skusFor('blank'))->toContain('lacks-'.$type)->not->toContain('has-'.$type);
    })->with(['image', 'gallery', 'file']);
});
