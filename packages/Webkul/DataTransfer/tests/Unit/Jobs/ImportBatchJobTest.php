<?php

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Webkul\DataTransfer\Jobs\Import\ImportBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

describe('ImportBatch Job', function () {

    it('implements ShouldQueue interface', function () {
        $interfaces = class_implements(ImportBatch::class);

        expect($interfaces)->toHaveKey(ShouldQueue::class);
    });

    it('can be dispatched to the queue', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
        ]);

        ImportBatch::dispatch($batch, $jobTrack->id);

        Queue::assertPushed(ImportBatch::class);
    });

    it('is dispatched with the correct constructor arguments', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
        ]);

        ImportBatch::dispatch($batch, $jobTrack->id);

        Queue::assertPushed(ImportBatch::class, function ($job) use ($batch, $jobTrack) {
            $reflection = new ReflectionClass($job);

            $importBatchProp = $reflection->getProperty('importBatch');
            $importBatchProp->setAccessible(true);

            $jobTrackIdProp = $reflection->getProperty('jobTrackId');
            $jobTrackIdProp->setAccessible(true);

            return $importBatchProp->getValue($job)->id === $batch->id
                && $jobTrackIdProp->getValue($job) === $jobTrack->id;
        });
    });

    it('sets the batch state to failed when the failed method is called', function () {
        $jobTrack = JobTrack::factory()->create();
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'state'        => 'pending',
        ]);

        $job = new ImportBatch($batch, $jobTrack->id);

        $exception = new RuntimeException('Test failure');
        $job->failed($exception);

        $batch->refresh();

        expect($batch->state)->toBe('failed');
    });

    it('sets the parent job track state to failed when a batch job fails', function () {
        $jobTrack = JobTrack::factory()->create(['state' => 'processing']);
        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'state'        => 'pending',
        ]);

        $job = new ImportBatch($batch, $jobTrack->id);

        $exception = new RuntimeException('Import batch failed');
        $job->failed($exception);

        $jobTrack->refresh();

        expect($jobTrack->state)->toBe('failed');
    });

    it('uses the Batchable trait', function () {
        $traits = class_uses_recursive(ImportBatch::class);

        expect($traits)->toContain(Batchable::class);
    });

    it('uses the Dispatchable trait', function () {
        $traits = class_uses_recursive(ImportBatch::class);

        expect($traits)->toContain(Dispatchable::class);
    });

    it('uses the InteractsWithQueue trait', function () {
        $traits = class_uses_recursive(ImportBatch::class);

        expect($traits)->toContain(InteractsWithQueue::class);
    });

    it('uses the SerializesModels trait', function () {
        $traits = class_uses_recursive(ImportBatch::class);

        expect($traits)->toContain(SerializesModels::class);
    });
});
