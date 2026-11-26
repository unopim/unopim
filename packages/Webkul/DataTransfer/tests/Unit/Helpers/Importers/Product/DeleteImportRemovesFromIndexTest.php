<?php

use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Support\Facades\Storage;
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
 * Run one delete-import batch for the given SKUs.
 *
 * @param  array<int, string>  $skus
 */
function runDeleteImportBatch(array $skus): void
{
    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => array_map(fn (string $sku): array => ['sku' => $sku], $skus),
    ]);

    app(Importer::class)->setImport($jobTrack)->importBatch($batch->refresh());
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

    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [['sku' => $product->sku]],
    ]);

    app(Importer::class)->setImport($jobTrack)->importBatch($batch->refresh());

    expect($batch->refresh()->state)->toBe(Import::STATE_PROCESSED)
        ->and(Storage::exists('product/'.$product->id))->toBeFalse();

    assertDatabaseMissing($this->getFullTableName(Product::class), ['id' => $product->id]);
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
