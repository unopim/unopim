<?php

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;
use Webkul\DataTransfer\Jobs\Export\Completed as ExportCompleted;
use Webkul\DataTransfer\Models\JobTrack;

describe('Export Completed Job', function () {

    it('implements ShouldQueue interface', function () {
        $interfaces = class_implements(ExportCompleted::class);

        expect($interfaces)->toHaveKey(ShouldQueue::class);
    });

    it('can be dispatched to the queue', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->export()->create();

        ExportCompleted::dispatch($jobTrack, $jobTrack->id, []);

        Queue::assertPushed(ExportCompleted::class);
    });

    it('is dispatched with the correct constructor arguments', function () {
        Queue::fake();

        $jobTrack = JobTrack::factory()->export()->create();
        $buffer = ['format' => 'csv'];

        ExportCompleted::dispatch($jobTrack, $jobTrack->id, $buffer);

        Queue::assertPushed(ExportCompleted::class, function ($job) use ($jobTrack, $buffer) {
            $reflection = new ReflectionClass($job);

            $exportProp = $reflection->getProperty('export');
            $exportProp->setAccessible(true);

            $jobTrackIdProp = $reflection->getProperty('jobTrackId');
            $jobTrackIdProp->setAccessible(true);

            $exportBufferProp = $reflection->getProperty('exportBuffer');
            $exportBufferProp->setAccessible(true);

            return $exportProp->getValue($job)->id === $jobTrack->id
                && $jobTrackIdProp->getValue($job) === $jobTrack->id
                && $exportBufferProp->getValue($job) === $buffer;
        });
    });

    it('uses the Dispatchable trait', function () {
        $traits = class_uses_recursive(ExportCompleted::class);

        expect($traits)->toContain(Dispatchable::class);
    });

    it('uses the InteractsWithQueue trait', function () {
        $traits = class_uses_recursive(ExportCompleted::class);

        expect($traits)->toContain(InteractsWithQueue::class);
    });

    it('uses the Queueable trait', function () {
        $traits = class_uses_recursive(ExportCompleted::class);

        expect($traits)->toContain(Queueable::class);
    });

    it('uses the SerializesModels trait', function () {
        $traits = class_uses_recursive(ExportCompleted::class);

        expect($traits)->toContain(SerializesModels::class);
    });

    it('does not use the Batchable trait', function () {
        $traits = class_uses_recursive(ExportCompleted::class);

        expect($traits)->not->toContain(Batchable::class);
    });
});
