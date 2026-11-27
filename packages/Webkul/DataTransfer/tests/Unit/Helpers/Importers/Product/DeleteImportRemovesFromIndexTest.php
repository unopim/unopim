<?php

use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
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
 * Delete importer that sends the index deletes in chunks of two, so a few products span several bulk calls.
 */
class TwoPerChunkDeleteImporter extends Importer
{
    protected const INDEX_DELETE_CHUNK_SIZE = 2;
}

/**
 * Run one delete-import batch for the given SKUs and return the processed batch.
 *
 * @param  array<int, string>  $skus
 * @param  class-string<Importer>  $importerClass
 */
function runDeleteImportBatch(array $skus, string $importerClass = Importer::class, ?LoggerInterface $logger = null): JobTrackBatch
{
    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => array_map(fn (string $sku): array => ['sku' => $sku], $skus),
    ]);

    $importer = app($importerClass)->setImport($jobTrack);

    if ($logger) {
        $importer->setLogger($logger);
    }

    $importer->importBatch($batch->refresh());

    return $batch->refresh();
}

/**
 * Collect the document ids from the delete actions of a bulk payload.
 *
 * @param  array<string, mixed>  $args
 * @return array<int, int>
 */
function deletedDocumentIds(array $args): array
{
    return collect($args['body'] ?? [])
        ->pluck('delete')
        ->filter()
        ->each(function (array $action): void {
            expect($action['_index'])->toBe('testing_products');
        })
        ->pluck('_id')
        ->map(fn (int|string $id): int => (int) $id)
        ->sort()
        ->values()
        ->all();
}

it('removes the deleted products from the elasticsearch index', function (): void {
    $products = Product::factory()->count(2)->create();

    config(['elasticsearch.enabled' => true]);

    $sentIds = null;

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) use (&$sentIds): bool {
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

it('also removes the variants the database cascade deletes with their parent', function (): void {
    $parent = Product::factory()->configurable()->withVariantProduct()->create();

    $variantIds = Product::query()->where('parent_id', $parent->id)->pluck('id')->all();

    expect($variantIds)->not->toBeEmpty();

    config(['elasticsearch.enabled' => true]);

    $sentIds = null;

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) use (&$sentIds): bool {
            $sentIds = deletedDocumentIds($args);

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    runDeleteImportBatch([$parent->sku]);

    expect($sentIds)->toBe(collect([$parent->id, ...$variantIds])->sort()->values()->all());
});

it('also removes the variants two levels below the deleted parent', function (): void {
    $parent = Product::factory()->configurable()->withVariantProduct()->create();

    $variantId = Product::query()->where('parent_id', $parent->id)->value('id');

    $grandchild = Product::factory()->create(['parent_id' => $variantId]);

    config(['elasticsearch.enabled' => true]);

    $sentIds = null;

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) use (&$sentIds): bool {
            $sentIds = deletedDocumentIds($args);

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    runDeleteImportBatch([$parent->sku]);

    expect($sentIds)->toBe(collect([$parent->id, $variantId, $grandchild->id])->sort()->values()->all());
});

it('still finishes the batch and removes the images when elasticsearch is unreachable', function (): void {
    Storage::fake();

    $product = Product::factory()->create();

    Storage::put('product/'.$product->id.'/image.jpg', 'image');

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->andThrow(new NoNodeAvailableException('No alive nodes. All the 1 nodes seem to be down.'));

    $jobLogger = Mockery::spy(LoggerInterface::class);

    $batch = runDeleteImportBatch([$product->sku], logger: $jobLogger);

    expect($batch->state)->toBe(Import::STATE_PROCESSED)
        ->and(Storage::exists('product/'.$product->id))->toBeFalse();

    $jobLogger->shouldHaveReceived('warning')->once()->with(trans('data_transfer::app.job.search-index-out-of-sync', ['count' => 1]));

    assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $product->id]);
});

it('sends the index deletes in several bulk requests when they exceed one chunk', function (): void {
    $products = Product::factory()->count(5)->create();

    config(['elasticsearch.enabled' => true]);

    $chunks = [];

    ElasticSearch::shouldReceive('bulk')
        ->times(3)
        ->withArgs(function (array $args) use (&$chunks): bool {
            $chunks[] = deletedDocumentIds($args);

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    runDeleteImportBatch($products->pluck('sku')->all(), TwoPerChunkDeleteImporter::class);

    expect(array_map(count(...), $chunks))->toBe([2, 2, 1])
        ->and(collect($chunks)->flatten()->sort()->values()->all())->toBe($products->pluck('id')->sort()->values()->all());
});

it('logs the documents elasticsearch failed to delete in every chunk', function (): void {
    $products = Product::factory()->count(3)->create();

    config(['elasticsearch.enabled' => true]);

    $failedIds = $products->pluck('id')->all();

    ElasticSearch::shouldReceive('bulk')
        ->times(2)
        ->andReturnUsing(fn (array $args): array => [
            'errors' => true,
            'items'  => array_map(fn (int $id): array => ['delete' => ['_id' => $id, 'error' => ['type' => 'version_conflict']]], deletedDocumentIds($args)),
        ]);

    $logger = Mockery::spy();

    Log::shouldReceive('channel')->with('elasticsearch')->andReturn($logger);

    runDeleteImportBatch($products->pluck('sku')->all(), TwoPerChunkDeleteImporter::class);

    foreach ($failedIds as $id) {
        $logger->shouldHaveReceived('error')->withArgs(fn (string $message): bool => str_contains($message, 'product id: '.$id.' '));
    }
});

it('does not call elasticsearch when it is disabled', function (): void {
    $product = Product::factory()->create();

    ElasticSearch::shouldReceive('bulk')->never();

    runDeleteImportBatch([$product->sku]);

    assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $product->id]);
});

it('does not call elasticsearch when no listed sku exists', function (): void {
    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('bulk')->never();

    runDeleteImportBatch(['missing-sku-'.uniqid()]);
});
