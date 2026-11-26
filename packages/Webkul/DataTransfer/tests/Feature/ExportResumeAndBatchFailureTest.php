<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Jobs\Export\ExportBatch;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Services\JobLogger;

function exportBufferOf(Export $helper): JSONFileBuffer
{
    $property = new ReflectionProperty($helper->getTypeExporter(), 'exportBuffer');

    return $property->getValue($helper->getTypeExporter());
}

function freshExportHelper(JobTrack $jobTrack): Export
{
    return resolve(Export::class)
        ->setExport($jobTrack->fresh())
        ->setLogger(JobLogger::make($jobTrack->id));
}

beforeEach(function () {
    Storage::fake();
    Bus::fake();
    config(['elasticsearch.enabled' => false]);
});

it('keeps the rows buffered before a pause when the export is resumed', function () {
    $jobTrack = JobTrack::factory()->export()->create(['state' => Export::STATE_PROCESSING]);

    JobTrackBatch::factory()->create(['job_track_id' => $jobTrack->id, 'state' => Export::STATE_PENDING]);

    $firstRun = freshExportHelper($jobTrack);
    $firstRun->start();

    $buffer = exportBufferOf($firstRun);
    $buffer->write(['sku' => 'exported-before-pause']);

    $firstRun->pause();

    $resumed = freshExportHelper($jobTrack);
    $resumed->resume();

    $resumedBuffer = exportBufferOf($resumed);
    $resumedBuffer->write(['sku' => 'exported-after-resume']);

    expect($resumedBuffer->getFilename())->toBe($buffer->getFilename())
        ->and(iterator_to_array($resumedBuffer, false))->toBe([
            ['sku' => 'exported-before-pause'],
            ['sku' => 'exported-after-resume'],
        ]);

    $resumedBuffer->delete();
});

it('starts a fresh buffer when the export is started again from scratch', function () {
    $jobTrack = JobTrack::factory()->export()->create(['state' => Export::STATE_PROCESSING]);

    JobTrackBatch::factory()->create(['job_track_id' => $jobTrack->id, 'state' => Export::STATE_PENDING]);

    $firstRun = freshExportHelper($jobTrack);
    $firstRun->start();

    $staleBuffer = exportBufferOf($firstRun);
    $staleBuffer->write(['sku' => 'stale-row']);
    $staleFilename = $staleBuffer->getFilename();

    $secondRun = freshExportHelper($jobTrack);
    $secondRun->started();
    $secondRun->start();

    $freshBuffer = exportBufferOf($secondRun);

    expect($freshBuffer->getFilename())->not->toBe($staleFilename)
        ->and(file_exists($staleFilename))->toBeFalse()
        ->and(iterator_to_array($freshBuffer, false))->toBe([]);

    $freshBuffer->delete();
});

it('ignores a stored buffer path that does not belong to the export', function () {
    $jobTrack = JobTrack::factory()->export()->create(['state' => Export::STATE_PROCESSING]);

    JobTrackBatch::factory()->create(['job_track_id' => $jobTrack->id, 'state' => Export::STATE_PENDING]);

    $foreign = tempnam(sys_get_temp_dir(), 'not_an_export_buffer_');

    $jobTrack->update(['meta' => array_merge((array) $jobTrack->meta, ['export_buffer' => $foreign])]);

    $helper = freshExportHelper($jobTrack);
    $helper->start();

    $buffer = exportBufferOf($helper);

    expect($buffer->getFilename())->not->toBe($foreign)
        ->and(file_exists($foreign))->toBeTrue();

    @unlink($foreign);
    $buffer->delete();
});

it('fails the export job when an export batch fails', function () {
    $jobTrack = JobTrack::factory()->export()->create(['state' => Export::STATE_PROCESSING]);

    $batch = JobTrackBatch::factory()->create(['job_track_id' => $jobTrack->id, 'state' => Export::STATE_PENDING]);

    (new ExportBatch($batch, '/tmp/export.csv', $jobTrack->id, null))
        ->failed(new RuntimeException('Broken attribute value'));

    $jobTrack->refresh();

    expect($batch->fresh()->state)->toBe(Export::STATE_FAILED)
        ->and($jobTrack->state)->toBe(Export::STATE_FAILED)
        ->and($jobTrack->errors)->toContain('Broken attribute value')
        ->and($jobTrack->completed_at)->not->toBeNull();
});
