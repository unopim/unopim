<?php

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Webkul\DataTransfer\Jobs\Import\Completed as ImportCompleted;
use Webkul\DataTransfer\Models\JobTrack;

describe('Import Completed Job', function () {

    it('implements ShouldQueue interface', function () {
        $interfaces = class_implements(ImportCompleted::class);

        expect($interfaces)->toHaveKey(ShouldQueue::class);
    });

    it('can be dispatched to the queue', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->create();

        ImportCompleted::dispatch($jobTrack, $jobTrack->id);

        Queue::assertPushed(ImportCompleted::class);
    });

    it('is dispatched with the correct constructor arguments', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->create();

        ImportCompleted::dispatch($jobTrack, $jobTrack->id);

        Queue::assertPushed(ImportCompleted::class, function ($job) use ($jobTrack) {
            $reflection = new ReflectionClass($job);

            $importProp = $reflection->getProperty('import');
            $importProp->setAccessible(true);

            $jobTrackIdProp = $reflection->getProperty('jobTrackId');
            $jobTrackIdProp->setAccessible(true);

            return $importProp->getValue($job)->id === $jobTrack->id
                && $jobTrackIdProp->getValue($job) === $jobTrack->id;
        });
    });

    it('uses the Dispatchable trait', function () {
        $traits = class_uses_recursive(ImportCompleted::class);

        expect($traits)->toContain(Dispatchable::class);
    });

    it('uses the InteractsWithQueue trait', function () {
        $traits = class_uses_recursive(ImportCompleted::class);

        expect($traits)->toContain(InteractsWithQueue::class);
    });

    it('uses the Queueable trait', function () {
        $traits = class_uses_recursive(ImportCompleted::class);

        expect($traits)->toContain(Queueable::class);
    });

    it('uses the SerializesModels trait', function () {
        $traits = class_uses_recursive(ImportCompleted::class);

        expect($traits)->toContain(SerializesModels::class);
    });

    it('does not use the Batchable trait', function () {
        $traits = class_uses_recursive(ImportCompleted::class);

        expect($traits)->not->toContain(Batchable::class);
    });
});
