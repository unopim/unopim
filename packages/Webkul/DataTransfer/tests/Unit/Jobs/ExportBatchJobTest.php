<?php

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Webkul\DataTransfer\Jobs\Export\ExportBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

describe('ExportBatch Job', function () {

    it('implements ShouldQueue interface', function () {
        $interfaces = class_implements(ExportBatch::class);

        expect($interfaces)->toHaveKey(ShouldQueue::class);
    });

    it('has correct tries property set to 3', function () {
        $jobTrack = JobTrack::factory()->export()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
        ]);

        $job = new ExportBatch($batch, '/tmp/export.csv', $jobTrack->id, []);

        expect($job->tries)->toBe(3);
    });

    it('has correct timeout property set to 600', function () {
        $jobTrack = JobTrack::factory()->export()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
        ]);

        $job = new ExportBatch($batch, '/tmp/export.csv', $jobTrack->id, []);

        expect($job->timeout)->toBe(600);
    });

    it('can be dispatched to the queue', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->export()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
        ]);

        ExportBatch::dispatch($batch, '/tmp/export.csv', $jobTrack->id, []);

        Queue::assertPushed(ExportBatch::class);
    });

    it('is dispatched with the correct constructor arguments', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->export()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
        ]);

        $filePath = '/tmp/export-test.csv';
        $buffer = ['key' => 'value'];

        ExportBatch::dispatch($batch, $filePath, $jobTrack->id, $buffer);

        Queue::assertPushed(ExportBatch::class, function ($job) use ($batch, $filePath, $jobTrack, $buffer) {
            $reflection = new ReflectionClass($job);

            $exportBatchProp = $reflection->getProperty('exportBatch');
            $exportBatchProp->setAccessible(true);

            $filePathProp = $reflection->getProperty('filePath');
            $filePathProp->setAccessible(true);

            $jobTrackIdProp = $reflection->getProperty('jobTrackId');
            $jobTrackIdProp->setAccessible(true);

            $exportBufferProp = $reflection->getProperty('exportBuffer');
            $exportBufferProp->setAccessible(true);

            return $exportBatchProp->getValue($job)->id === $batch->id
                && $filePathProp->getValue($job) === $filePath
                && $jobTrackIdProp->getValue($job) === $jobTrack->id
                && $exportBufferProp->getValue($job) === $buffer;
        });
    });

    it('sets the batch state to failed when the failed method is called', function () {
        $jobTrack = JobTrack::factory()->export()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'state'        => 'pending',
        ]);

        $job = new ExportBatch($batch, '/tmp/export.csv', $jobTrack->id, []);

        $exception = new RuntimeException('Export test failure');
        $job->failed($exception);

        $batch->refresh();

        expect($batch->state)->toBe('failed');
    });

    it('uses the Batchable trait', function () {
        $traits = class_uses_recursive(ExportBatch::class);

        expect($traits)->toContain(Batchable::class);
    });

    it('uses the Dispatchable trait', function () {
        $traits = class_uses_recursive(ExportBatch::class);

        expect($traits)->toContain(Dispatchable::class);
    });

    it('uses the SerializesModels trait', function () {
        $traits = class_uses_recursive(ExportBatch::class);

        expect($traits)->toContain(SerializesModels::class);
    });
});
