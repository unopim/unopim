<?php

use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Jobs\Import\LinkBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Tests\Support\FilelessTestImporter;
use Webkul\DataTransfer\Tests\Support\LinkingTestImporter;

function linkStageBatch(): JobTrackBatch
{
    return JobTrackBatch::factory()->create([
        'job_track_id' => JobTrack::factory()->create()->id,
        'data'         => [['code' => 'linked-row']],
    ]);
}

function runLinkBatchJobWith(FilelessTestImporter $importer, JobTrackBatch $batch): void
{
    $importHelper = Mockery::mock(ImportHelper::class);
    $importHelper->shouldReceive('setImport')->andReturnSelf();
    $importHelper->shouldReceive('setLogger')->andReturnSelf();
    $importHelper->shouldReceive('shouldStop')->andReturnFalse();
    $importHelper->shouldReceive('getTypeImporter')->andReturn($importer);

    app()->instance(ImportHelper::class, $importHelper);

    (new LinkBatch($batch, $batch->job_track_id))->handle();
}

it('links a batch through an importer that needs no link step without failing', function () {
    expect(app(FilelessTestImporter::class)->linkData(linkStageBatch()))->toBeTrue();
});

it('runs the link batch job for an importer that needs no link step without failing', function () {
    $batch = linkStageBatch();

    runLinkBatchJobWith(app(FilelessTestImporter::class), $batch);

    expect($batch->refresh()->state)->not->toBe('failed');
});

it('hands the batch to the importer link step when the importer defines one', function () {
    $batch = linkStageBatch();

    $importer = app(LinkingTestImporter::class);

    expect($importer->linkData($batch))->toBeTrue()
        ->and($importer->linkedBatchIds)->toBe([$batch->id]);
});

it('runs the importer link step from the link batch job', function () {
    $batch = linkStageBatch();

    $importer = app(LinkingTestImporter::class);

    runLinkBatchJobWith($importer, $batch);

    expect($importer->linkedBatchIds)->toBe([$batch->id]);
});
