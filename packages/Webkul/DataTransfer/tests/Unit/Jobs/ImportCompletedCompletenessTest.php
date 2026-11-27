<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\DataTransfer\Jobs\Import\Completed as ImportCompleted;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

/**
 * Run the post-import completeness step of the completed job for the given import.
 */
function importCompletedCompletenessRun(JobTrack $import): void
{
    $job = new ImportCompleted($import, $import->id);

    (fn () => $this->dispatchPostImportCompleteness())->call($job);
}

/**
 * Return the product ids sent to every dispatched bulk completeness job.
 *
 * @return array<int, int>
 */
function importCompletedCompletenessDispatchedIds(): array
{
    return Bus::dispatched(BulkProductCompletenessJob::class)
        ->flatMap(fn (BulkProductCompletenessJob $job): array => (fn (): array => $this->product)->call($job))
        ->sort()
        ->values()
        ->all();
}

beforeEach(function (): void {
    Bus::fake([BulkProductCompletenessJob::class]);
});

it('resolves completeness for every imported product when the import holds more skus than the placeholder limit', function (): void {
    $products = Product::factory()->count(3)->create();

    $rows = array_map(fn (int $index): array => ['sku' => 'icc-missing-'.$index], range(1, 66000));

    foreach ($products as $product) {
        $rows[] = ['sku' => $product->sku];
    }

    $import = JobTrack::factory()->create();

    JobTrackBatch::factory()->processed()->create(['job_track_id' => $import->id, 'data' => $rows]);

    importCompletedCompletenessRun($import);

    expect(importCompletedCompletenessDispatchedIds())->toBe($products->pluck('id')->sort()->values()->all());
});

it('collects skus across several processed batches without duplicates', function (): void {
    $products = Product::factory()->count(2)->create();

    $import = JobTrack::factory()->create();

    JobTrackBatch::factory()->processed()->create(['job_track_id' => $import->id, 'data' => [['sku' => $products[0]->sku], ['sku' => $products[1]->sku]]]);
    JobTrackBatch::factory()->processed()->create(['job_track_id' => $import->id, 'data' => [['sku' => $products[1]->sku], ['sku' => '']]]);

    importCompletedCompletenessRun($import);

    expect(importCompletedCompletenessDispatchedIds())->toBe($products->pluck('id')->sort()->values()->all());
});

it('ignores batches that were not processed', function (): void {
    $product = Product::factory()->create();

    $import = JobTrack::factory()->create();

    JobTrackBatch::factory()->create(['job_track_id' => $import->id, 'state' => 'pending', 'data' => [['sku' => $product->sku]]]);

    importCompletedCompletenessRun($import);

    Bus::assertNotDispatched(BulkProductCompletenessJob::class);
});

it('does not dispatch completeness when no imported sku matches a product', function (): void {
    $import = JobTrack::factory()->create();

    JobTrackBatch::factory()->processed()->create(['job_track_id' => $import->id, 'data' => [['sku' => 'icc-unknown-sku']]]);

    importCompletedCompletenessRun($import);

    Bus::assertNotDispatched(BulkProductCompletenessJob::class);
});

it('resolves a product whose sku is numeric', function (): void {
    $product = Product::factory()->create(['sku' => '100200300']);

    $import = JobTrack::factory()->create();

    JobTrackBatch::factory()->processed()->create(['job_track_id' => $import->id, 'data' => [['sku' => '100200300']]]);

    importCompletedCompletenessRun($import);

    expect(importCompletedCompletenessDispatchedIds())->toBe([$product->id]);
});
