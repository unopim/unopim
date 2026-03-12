<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

function createTestJobInstance(): JobInstances
{
    return JobInstances::create([
        'code'                => 'test-import-'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => 'skip-errors',
        'allowed_errors'      => 10,
        'field_separator'     => ',',
        'file_path'           => 'imports/test.csv',
    ]);
}

function createTestJobTrack(JobInstances $jobInstance, string $state = 'processing'): JobTrack
{
    return JobTrack::create([
        'state'               => $state,
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => 'skip-errors',
        'allowed_errors'      => 10,
        'field_separator'     => ',',
        'file_path'           => 'imports/test.csv',
        'meta'                => json_encode($jobInstance->toArray()),
        'job_instances_id'    => $jobInstance->id,
        'user_id'             => 1,
        'started_at'          => now(),
    ]);
}

describe('Import Pause/Resume/Cancel', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
        Event::fake();
    });

    it('can pause a processing import', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        $importHelper = app(Import::class)->setImport($jobTrack);
        $importHelper->pause();

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_PAUSED);

        Event::assertDispatched('data_transfer.imports.paused');

        $jobInstance->delete();
    });

    it('can resume a paused import', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PAUSED);

        $importHelper = app(Import::class)->setImport($jobTrack);
        $importHelper->resume();

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_PROCESSING);

        Event::assertDispatched('data_transfer.imports.resumed');

        $jobInstance->delete();
    });

    it('can cancel a processing import', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        $importHelper = app(Import::class)->setImport($jobTrack);
        $importHelper->cancel();

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_CANCELLED);
        expect($jobTrack->completed_at)->not->toBeNull();
        expect($jobTrack->summary)->toBeArray();
        expect($jobTrack->summary)->toHaveKeys(['created', 'updated', 'deleted']);

        Event::assertDispatched('data_transfer.imports.cancelled');

        $jobInstance->delete();
    });

    it('can cancel a paused import', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PAUSED);

        $importHelper = app(Import::class)->setImport($jobTrack);
        $importHelper->cancel();

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_CANCELLED);
        expect($jobTrack->completed_at)->not->toBeNull();

        $jobInstance->delete();
    });

    it('aggregates partial summary on cancel', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        JobTrackBatch::create([
            'state'        => Import::STATE_PROCESSED,
            'data'         => [['sku' => 'test1']],
            'summary'      => ['created' => 5, 'updated' => 3, 'deleted' => 0],
            'job_track_id' => $jobTrack->id,
        ]);

        JobTrackBatch::create([
            'state'        => Import::STATE_PROCESSED,
            'data'         => [['sku' => 'test2']],
            'summary'      => ['created' => 2, 'updated' => 1, 'deleted' => 1],
            'job_track_id' => $jobTrack->id,
        ]);

        $importHelper = app(Import::class)->setImport($jobTrack);
        $importHelper->cancel();

        $jobTrack->refresh();
        expect((int) $jobTrack->summary['created'])->toBe(7);
        expect((int) $jobTrack->summary['updated'])->toBe(4);
        expect((int) $jobTrack->summary['deleted'])->toBe(1);

        $jobInstance->delete();
    });
});

describe('Import shouldStop', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('returns true when import is paused', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PAUSED);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeTrue();

        $jobInstance->delete();
    });

    it('returns true when import is cancelled', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_CANCELLED);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeTrue();

        $jobInstance->delete();
    });

    it('returns true when import is failed', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_FAILED);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeTrue();

        $jobInstance->delete();
    });

    it('returns false when import is processing', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeFalse();

        $jobInstance->delete();
    });

    it('returns false when import is linking', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_LINKING);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeFalse();

        $jobInstance->delete();
    });

    it('returns false when import is indexing', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_INDEXING);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeFalse();

        $jobInstance->delete();
    });

    it('detects state change via database refresh', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        $importHelper = app(Import::class)->setImport($jobTrack);
        expect($importHelper->shouldStop())->toBeFalse();

        /** Simulate external state change (e.g., user clicked pause in UI) */
        DB::table('job_track')->where('id', $jobTrack->id)->update(['state' => Import::STATE_PAUSED]);

        expect($importHelper->shouldStop())->toBeTrue();

        $jobInstance->delete();
    });
});

describe('Import Controller Pause/Resume/Cancel', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('can pause an import via controller endpoint', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        $response = $this->postJson(route('admin.settings.data_transfer.imports.pause', $jobTrack->id));

        $response->assertOk();
        $response->assertJsonStructure(['message']);

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_PAUSED);

        $jobInstance->delete();
    });

    it('can resume an import via controller endpoint', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PAUSED);

        $response = $this->postJson(route('admin.settings.data_transfer.imports.resume', $jobTrack->id));

        $response->assertOk();
        $response->assertJsonStructure(['message']);

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_PROCESSING);

        $jobInstance->delete();
    });

    it('can cancel an import via controller endpoint', function () {
        $jobInstance = createTestJobInstance();
        $jobTrack = createTestJobTrack($jobInstance, Import::STATE_PROCESSING);

        $response = $this->postJson(route('admin.settings.data_transfer.imports.cancel', $jobTrack->id));

        $response->assertOk();
        $response->assertJsonStructure(['message']);

        $jobTrack->refresh();
        expect($jobTrack->state)->toBe(Import::STATE_CANCELLED);
        expect($jobTrack->completed_at)->not->toBeNull();

        $jobInstance->delete();
    });
});

describe('Import State Constants', function () {
    it('has paused state constant', function () {
        expect(Import::STATE_PAUSED)->toBe('paused');
    });

    it('has cancelled state constant', function () {
        expect(Import::STATE_CANCELLED)->toBe('cancelled');
    });
});
