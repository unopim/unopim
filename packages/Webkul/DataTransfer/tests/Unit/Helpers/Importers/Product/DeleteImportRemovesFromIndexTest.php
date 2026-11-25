<?php

use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    config([
        'elasticsearch.enabled'                     => false,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
    ]);

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn(Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient'))
        ->zeroOrMoreTimes();
});

/**
 * @param  array<int, string>  $skus
 */
function runDeleteImportBatch(array $skus): void
{
    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => array_map(fn (string $sku) => ['sku' => $sku], $skus),
    ]);

    app(Importer::class)->setImport($jobTrack)->importBatch($batch->refresh());
}

/**
 * @return array<int, int>
 */
function deletedDocumentIds(array $args): array
{
    return collect($args['body'] ?? [])
        ->pluck('delete')
        ->filter()
        ->each(fn (array $action) => expect($action['_index'])->toBe('testing_products'))
        ->pluck('_id')
        ->map(fn ($id) => (int) $id)
        ->sort()
        ->values()
        ->all();
}

it('removes the deleted products from the elasticsearch index', function () {
    $products = Product::factory()->count(2)->create();

    config(['elasticsearch.enabled' => true]);

    $sentIds = null;

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) use (&$sentIds) {
            $sentIds = deletedDocumentIds($args);

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    runDeleteImportBatch($products->pluck('sku')->all());

    expect($sentIds)->toBe($products->pluck('id')->sort()->values()->all());

    foreach ($products as $product) {
        assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $product->id]);
    }
});

it('also removes the variants the database cascade deletes with their parent', function () {
    $parent = Product::factory()->configurable()->withVariantProduct()->create();

    $variantIds = Product::query()->where('parent_id', $parent->id)->pluck('id')->all();

    expect($variantIds)->not->toBeEmpty();

    config(['elasticsearch.enabled' => true]);

    $sentIds = null;

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) use (&$sentIds) {
            $sentIds = deletedDocumentIds($args);

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    runDeleteImportBatch([$parent->sku]);

    expect($sentIds)->toBe(collect([$parent->id, ...$variantIds])->sort()->values()->all());
});

it('does not call elasticsearch when it is disabled', function () {
    $product = Product::factory()->create();

    ElasticSearch::shouldReceive('bulk')->never();

    runDeleteImportBatch([$product->sku]);

    assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $product->id]);
});

it('does not call elasticsearch when no listed sku exists', function () {
    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('bulk')->never();

    runDeleteImportBatch(['missing-sku-'.uniqid()]);
});
