<?php

use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Jobs\Import\IndexBatch;
use Webkul\DataTransfer\Jobs\Import\LinkBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Tests\Support\FilelessTestImporter;
use Webkul\DataTransfer\Tests\Support\LinkingTestImporter;
use Webkul\DataTransfer\Tests\Support\StageRequiredTestImporter;

/**
 * Create a job track batch for the link stage tests.
 */
function linkStageBatch(): JobTrackBatch
{
    return JobTrackBatch::factory()->create([
        'job_track_id' => JobTrack::factory()->create()->id,
        'data'         => [['code' => 'linked-row']],
    ]);
}

/**
 * Bind an import helper that hands out the given importer.
 */
function bindImportHelperFor(AbstractImporter $importer): void
{
    $importHelper = Mockery::mock(ImportHelper::class);
    $importHelper->shouldReceive('setImport')->andReturnSelf();
    $importHelper->shouldReceive('setLogger')->andReturnSelf();
    $importHelper->shouldReceive('shouldStop')->andReturnFalse();
    $importHelper->shouldReceive('getTypeImporter')->andReturn($importer);

    app()->instance(ImportHelper::class, $importHelper);
}

/**
 * Run the LinkBatch job against the given importer.
 */
function runLinkBatchJobWith(AbstractImporter $importer, JobTrackBatch $batch): void
{
    bindImportHelperFor($importer);

    (new LinkBatch($batch, $batch->job_track_id))->handle();
}

/**
 * Run the IndexBatch job against the given importer.
 */
function runIndexBatchJobWith(AbstractImporter $importer, JobTrackBatch $batch): void
{
    bindImportHelperFor($importer);

    (new IndexBatch($batch, $batch->job_track_id))->handle();
}

it('links a batch through an importer that needs no link step without failing', function (): void {
    expect(app(FilelessTestImporter::class)->linkData(linkStageBatch()))->toBeTrue();
});

it('runs the link batch job for an importer that needs no link step without failing', function (): void {
    $batch = linkStageBatch();

    expect(function () use ($batch): void {
        runLinkBatchJobWith(app(FilelessTestImporter::class), $batch);
    })->not->toThrow(Throwable::class);
});

it('hands the batch to the importer link step when the importer defines one', function (): void {
    $batch = linkStageBatch();

    $importer = app(LinkingTestImporter::class);

    expect($importer->linkData($batch))->toBeTrue()
        ->and($importer->linkedBatchIds)->toBe([$batch->id]);
});

it('runs the importer link step from the link batch job', function (): void {
    $batch = linkStageBatch();

    $importer = app(LinkingTestImporter::class);

    runLinkBatchJobWith($importer, $batch);

    expect($importer->linkedBatchIds)->toBe([$batch->id]);
});

it('fails loudly when an importer requires linking but defines no link step', function (): void {
    expect(fn (): bool => app(StageRequiredTestImporter::class)->linkData(linkStageBatch()))
        ->toThrow(LogicException::class, 'defines no linkBatch()');
});

it('runs the index batch job for an importer that needs no index step without failing', function (): void {
    $batch = linkStageBatch();

    expect(function () use ($batch): void {
        runIndexBatchJobWith(app(FilelessTestImporter::class), $batch);
    })->not->toThrow(Throwable::class);
});

it('fails loudly when an importer requires indexing but defines no index step', function (): void {
    expect(fn (): bool => app(StageRequiredTestImporter::class)->indexData(linkStageBatch()))
        ->toThrow(LogicException::class, 'defines no indexBatch()');
});
