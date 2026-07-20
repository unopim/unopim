<?php

use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;
use Webkul\AiAgent\Jobs\IndexProductEmbeddingsJob;
use Webkul\AiAgent\Services\EmbeddingSimilarityService;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\Core\Facades\ElasticSearch;

it('reports disabled unless both the vector store and elasticsearch are enabled', function () {
    $index = new ProductEmbeddingIndex;

    config(['ai-agent.vector_store.enabled' => false, 'elasticsearch.enabled' => false]);
    expect($index->isEnabled())->toBeFalse();

    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => false]);
    expect($index->isEnabled())->toBeFalse();

    config(['ai-agent.vector_store.enabled' => false, 'elasticsearch.enabled' => true]);
    expect($index->isEnabled())->toBeFalse();

    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);
    expect($index->isEnabled())->toBeTrue();
});

it('returns empty from rankProducts without touching elasticsearch or embeddings when disabled', function () {
    config(['ai-agent.vector_store.enabled' => false, 'elasticsearch.enabled' => true]);

    Embeddings::fake();
    ElasticSearch::spy();

    $results = (new EmbeddingSimilarityService(new ProductEmbeddingIndex))->rankProducts('red running shoes', 5);

    expect($results)->toBe([]);

    Embeddings::assertNothingGenerated();
    ElasticSearch::shouldNotHaveReceived('search');
});

it('returns empty from rankProducts for a blank query even when enabled', function () {
    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);

    Embeddings::fake();
    ElasticSearch::spy();

    $results = (new EmbeddingSimilarityService(new ProductEmbeddingIndex))->rankProducts('   ', 5);

    expect($results)->toBe([]);

    Embeddings::assertNothingGenerated();
    ElasticSearch::shouldNotHaveReceived('search');
});

it('builds the index name from the elasticsearch prefix following package conventions', function () {
    config(['elasticsearch.prefix' => 'UnoPim']);

    expect((new ProductEmbeddingIndex)->indexName())->toBe('unopim_ai_product_embeddings');
});

it('builds a dense_vector mapping with the configured dimensions', function () {
    config(['ai-agent.vector_store.dimensions' => 768]);

    $mappings = (new ProductEmbeddingIndex)->mappings();

    expect($mappings['properties']['embedding'])->toBe([
        'type'       => 'dense_vector',
        'dims'       => 768,
        'index'      => true,
        'similarity' => 'cosine',
    ])
        ->and($mappings['properties']['product_id'])->toBe(['type' => 'long'])
        ->and($mappings['properties']['sku'])->toBe(['type' => 'keyword'])
        ->and($mappings['properties']['content_hash'])->toBe(['type' => 'keyword']);
});

it('defaults the mapping dimensions to 1536', function () {
    config(['ai-agent.vector_store.dimensions' => null]);

    expect((new ProductEmbeddingIndex)->mappings()['properties']['embedding']['dims'])->toBe(1536);
});

it('builds an embedding document with sku, textual values and a stable content hash', function () {
    $builder = new ProductEmbeddingDocumentBuilder;

    $values = [
        'common' => [
            'name'        => 'Red Running Shoes',
            'description' => '<p>Lightweight &amp; breathable</p>',
            'image'       => null,
        ],
        'locale_specific' => [
            'fr_FR' => ['name' => 'Chaussures de course rouges'],
        ],
    ];

    $document = $builder->build(42, 'SKU-42', $values);

    expect($document['product_id'])->toBe(42)
        ->and($document['sku'])->toBe('SKU-42')
        ->and($document['text'])->toContain('sku: SKU-42')
        ->and($document['text'])->toContain('name: Red Running Shoes')
        ->and($document['text'])->toContain('description: Lightweight & breathable')
        ->and($document['text'])->toContain('name: Chaussures de course rouges')
        ->and($document['text'])->not->toContain('<p>')
        ->and($document['content_hash'])->toBe(hash('sha256', $document['text']))
        ->and($builder->build(42, 'SKU-42', $values)['content_hash'])->toBe($document['content_hash']);
});

it('decodes raw json values and truncates the document to the configured length', function () {
    config(['ai-agent.vector_store.max_document_length' => 100]);

    $builder = new ProductEmbeddingDocumentBuilder;

    $document = $builder->build(7, 'SKU-7', json_encode([
        'common' => ['description' => str_repeat('long text ', 100)],
    ]));

    expect($document['text'])->toContain('sku: SKU-7')
        ->and(mb_strlen($document['text']))->toBeLessThanOrEqual(100)
        ->and($document['content_hash'])->toBe(hash('sha256', $document['text']));
});

it('does nothing in the indexing job when the vector store is disabled', function () {
    config(['ai-agent.vector_store.enabled' => false, 'elasticsearch.enabled' => true]);

    Embeddings::fake();
    ElasticSearch::spy();

    (new IndexProductEmbeddingsJob([1, 2, 3]))->handle(
        new ProductEmbeddingIndex,
        new ProductEmbeddingDocumentBuilder,
    );

    Embeddings::assertNothingGenerated();
    ElasticSearch::shouldNotHaveReceived('bulk');
    ElasticSearch::shouldNotHaveReceived('search');
});

it('maps knn hits to product ids and scores using a bounded k', function () {
    config([
        'ai-agent.vector_store.enabled'         => true,
        'elasticsearch.enabled'                 => true,
        'elasticsearch.prefix'                  => 'testing',
        'ai-agent.vector_store.knn.max_results' => 3,
    ]);

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(function ($args) {
            return $args['index'] === 'testing_ai_product_embeddings'
                && $args['body']['knn']['field'] === 'embedding'
                && $args['body']['knn']['k'] === 3
                && $args['body']['knn']['num_candidates'] >= 3
                && $args['body']['_source'] === ['product_id'];
        })
        ->andReturn([
            'hits' => [
                'hits' => [
                    ['_id' => '11', '_score' => 0.93, '_source' => ['product_id' => 11]],
                    ['_id' => '5', '_score' => 0.81, '_source' => ['product_id' => 5]],
                ],
            ],
        ]);

    $results = (new ProductEmbeddingIndex)->searchSimilar([0.1, 0.2, 0.3], 25);

    expect($results)->toBe([
        ['product_id' => 11, 'score' => 0.93],
        ['product_id' => 5, 'score' => 0.81],
    ]);
});

it('ranks products through the vector store when enabled', function () {
    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);

    Embeddings::fake([
        [array_fill(0, 8, 0.5)],
    ]);

    $index = Mockery::mock(ProductEmbeddingIndex::class);
    $index->shouldReceive('isEnabled')->andReturn(true);
    $index->shouldReceive('searchSimilar')
        ->once()
        ->withArgs(fn ($vector, $limit) => count($vector) === 8 && $limit === 4)
        ->andReturn([['product_id' => 9, 'score' => 0.88]]);

    $results = (new EmbeddingSimilarityService($index))->rankProducts('red shoes', 4);

    expect($results)->toBe([['product_id' => 9, 'score' => 0.88]]);
});

it('returns empty from rankProducts when the vector search fails', function () {
    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);

    Embeddings::fake();

    $index = Mockery::mock(ProductEmbeddingIndex::class);
    $index->shouldReceive('isEnabled')->andReturn(true);
    $index->shouldReceive('searchSimilar')->andThrow(new RuntimeException('es down'));

    expect((new EmbeddingSimilarityService($index))->rankProducts('red shoes', 4))->toBe([]);
});

it('warns and queues nothing when the index command runs while disabled', function () {
    config(['ai-agent.vector_store.enabled' => false, 'elasticsearch.enabled' => false]);

    Queue::fake();

    $this->artisan('ai-agent:embeddings:index')
        ->expectsOutputToContain('disabled')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

it('rejects an unparseable since option', function () {
    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);

    Queue::fake();
    ElasticSearch::spy();

    $this->artisan('ai-agent:embeddings:index', ['--since' => 'not-a-date'])
        ->assertExitCode(1);

    Queue::assertNothingPushed();
});
