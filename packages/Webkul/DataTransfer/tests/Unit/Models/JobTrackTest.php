<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

use function Pest\Laravel\assertDatabaseHas;

describe('JobTrack Model', function () {

    it('can be created with required fields via factory', function () {
        $jobTrack = JobTrack::factory()->create();

        expect($jobTrack)->toBeInstanceOf(JobTrack::class);
        expect($jobTrack->id)->toBeGreaterThan(0);

        assertDatabaseHas('job_track', [
            'id'    => $jobTrack->id,
            'state' => 'pending',
            'type'  => 'import',
        ]);
    });

    it('uses the correct table name', function () {
        $jobTrack = new JobTrack;

        expect($jobTrack->getTable())->toBe('job_track');
    });

    it('has the expected fillable attributes', function () {
        $jobTrack = new JobTrack;

        $expectedFillable = [
            'state',
            'type',
            'action',
            'validation_strategy',
            'validation_strategy',
            'allowed_errors',
            'processed_rows_count',
            'invalid_rows_count',
            'errors_count',
            'errors',
            'field_separator',
            'file_path',
            'images_directory_path',
            'error_file_path',
            'summary',
            'started_at',
            'completed_at',
            'meta',
            'job_instances_id',
            'user_id',
        ];

        expect($jobTrack->getFillable())->toBe($expectedFillable);
    });

    it('casts summary to array', function () {
        $summaryData = ['total' => 100, 'created' => 80, 'updated' => 20];

        $jobTrack = JobTrack::factory()->create([
            'summary' => $summaryData,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->summary)->toBeArray();
        expect($jobTrack->summary)->toBe($summaryData);
    });

    it('casts meta to array', function () {
        $metaData = ['batch_count' => 5, 'source' => 'csv'];

        $jobTrack = JobTrack::factory()->create([
            'meta' => $metaData,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->meta)->toBeArray();
        expect($jobTrack->meta)->toEqual($metaData);
    });

    it('casts errors to array', function () {
        $errorsData = ['Row 1: invalid SKU', 'Row 5: missing name'];

        $jobTrack = JobTrack::factory()->create([
            'errors' => $errorsData,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->errors)->toBeArray();
        expect($jobTrack->errors)->toBe($errorsData);
    });

    it('casts started_at to datetime', function () {
        $startedAt = now()->subMinutes(10);

        $jobTrack = JobTrack::factory()->create([
            'started_at' => $startedAt,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->started_at)->toBeInstanceOf(Carbon::class);
        expect($jobTrack->started_at->format('Y-m-d H:i'))->toBe($startedAt->format('Y-m-d H:i'));
    });

    it('casts completed_at to datetime', function () {
        $completedAt = now();

        $jobTrack = JobTrack::factory()->create([
            'completed_at' => $completedAt,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->completed_at)->toBeInstanceOf(Carbon::class);
        expect($jobTrack->completed_at->format('Y-m-d H:i'))->toBe($completedAt->format('Y-m-d H:i'));
    });

    it('has a batches HasMany relationship', function () {
        $jobTrack = JobTrack::factory()->create();

        expect($jobTrack->batches())->toBeInstanceOf(HasMany::class);
    });

    it('can retrieve associated batches', function () {
        $jobTrack = JobTrack::factory()->create();

        JobTrackBatch::factory()->count(3)->create([
            'job_track_id' => $jobTrack->id,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->batches)->toHaveCount(3);
        expect($jobTrack->batches->first())->toBeInstanceOf(JobTrackBatch::class);
    });

    it('has a jobInstance BelongsTo relationship', function () {
        $jobTrack = JobTrack::factory()->create();

        expect($jobTrack->jobInstance())->toBeInstanceOf(BelongsTo::class);
    });

    it('can retrieve the associated job instance', function () {
        $jobInstance = JobInstances::factory()->importJob()->create();

        $jobTrack = JobTrack::factory()->create([
            'job_instances_id' => $jobInstance->id,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->jobInstance)->toBeInstanceOf(JobInstances::class);
        expect($jobTrack->jobInstance->id)->toBe($jobInstance->id);
    });

    it('allows null values for nullable fields', function () {
        $jobTrack = JobTrack::factory()->create([
            'errors'                => null,
            'summary'               => null,
            'file_path'             => null,
            'images_directory_path' => null,
            'error_file_path'       => null,
            'started_at'            => null,
            'completed_at'          => null,
            'user_id'               => null,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->errors)->toBeNull();
        expect($jobTrack->summary)->toBeNull();
        expect($jobTrack->file_path)->toBeNull();
        expect($jobTrack->images_directory_path)->toBeNull();
        expect($jobTrack->error_file_path)->toBeNull();
        expect($jobTrack->started_at)->toBeNull();
        expect($jobTrack->completed_at)->toBeNull();
        expect($jobTrack->user_id)->toBeNull();
    });

    it('stores and retrieves processed rows count correctly', function () {
        $jobTrack = JobTrack::factory()->create([
            'processed_rows_count' => 250,
            'invalid_rows_count'   => 10,
            'errors_count'         => 5,
        ]);

        $jobTrack->refresh();

        expect($jobTrack->processed_rows_count)->toBe(250);
        expect($jobTrack->invalid_rows_count)->toBe(10);
        expect($jobTrack->errors_count)->toBe(5);
    });

    it('can create a completed job track using factory state', function () {
        $jobTrack = JobTrack::factory()->completed()->create();

        $jobTrack->refresh();

        expect($jobTrack->state)->toBe('completed');
        expect($jobTrack->started_at)->toBeInstanceOf(Carbon::class);
        expect($jobTrack->completed_at)->toBeInstanceOf(Carbon::class);
        expect($jobTrack->summary)->toBeArray();
        expect($jobTrack->summary)->toHaveKeys(['total', 'created', 'updated']);
    });

    it('can create an export job track using factory state', function () {
        $jobTrack = JobTrack::factory()->export()->create();

        $jobTrack->refresh();

        expect($jobTrack->type)->toBe('export');
        expect($jobTrack->action)->toBe('export');
    });
});
