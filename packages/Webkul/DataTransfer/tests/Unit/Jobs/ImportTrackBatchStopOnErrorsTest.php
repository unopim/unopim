<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\Completed;
use Webkul\DataTransfer\Jobs\Import\ImportBatch;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\DataTransfer\Tests\Support\StopOnErrorsTestImporter;

beforeEach(function (): void {
    Storage::fake('private');
    config([
        'queue.default'       => 'sync',
        'importers.stop_test' => [
            'importer'         => StopOnErrorsTestImporter::class,
            'has_file_options' => false,
        ],
    ]);
    StopOnErrorsTestImporter::$rows = [
        ['code' => 'valid-one', 'status' => '1'],
        ['code' => 'invalid-one', 'status' => 'invalid'],
        ['code' => 'valid-two', 'status' => '1'],
    ];
    StopOnErrorsTestImporter::$failValidation = false;
    StopOnErrorsTestImporter::$importedCodes = [];
    Event::fake([
        'data_transfer.imports.started',
        'data_transfer.imports.completed',
        'data_transfer.import.validate.state_failed',
    ]);
});

dataset('stopping validations', [
    'stop on first error'                        => [Import::VALIDATION_STRATEGY_STOP_ON_ERROR, 0, false],
    'stop on errors ignores allowed errors'      => [Import::VALIDATION_STRATEGY_STOP_ON_ERROR, 10, false],
    'validation exception even with skip errors' => [Import::VALIDATION_STRATEGY_SKIP_ERRORS, 10, true],
]);

/**
 * Create a job track for the stop-on-errors test importer with a clean job log.
 */
function makeStopOnErrorsTrack(string $strategy, int $allowedErrors, string $state = Import::STATE_PENDING): JobTrack
{
    $track = JobTrack::factory()->create([
        'state'               => $state,
        'validation_strategy' => $strategy,
        'allowed_errors'      => $allowedErrors,
    ]);
    $track->jobInstance->update(['entity_type' => 'stop_test']);

    File::delete(storage_path(JobLogger::getJobLogPath($track->id)));

    return $track;
}

it('does not execute pending batches after validation must stop', function (string $strategy, int $allowedErrors, bool $failValidation): void {
    StopOnErrorsTestImporter::$failValidation = $failValidation;
    $track = makeStopOnErrorsTrack($strategy, $allowedErrors);

    (new ImportTrackBatch($track))->handle();

    $track->refresh();
    expect($track->batches)->toHaveCount(1)
        ->and($track->batches->first()->state)->toBe(Import::STATE_PENDING)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([])
        ->and($track->state)->toBe(Import::STATE_FAILED)
        ->and($track->completed_at)->not->toBeNull()
        ->and($track->errors_count)->toBe($failValidation ? 2 : 1)
        ->and($track->invalid_rows_count)->toBe(1)
        ->and($track->errors)->not->toBeEmpty()
        ->and(Storage::disk('private')->exists($track->error_file_path))->toBeTrue();
    Event::assertNotDispatched('data_transfer.imports.started');
    Event::assertNotDispatched('data_transfer.imports.completed');
})->with('stopping validations');

it('logs why the import stopped after validation', function (string $strategy, int $allowedErrors, bool $failValidation): void {
    StopOnErrorsTestImporter::$failValidation = $failValidation;
    $track = makeStopOnErrorsTrack($strategy, $allowedErrors);

    (new ImportTrackBatch($track))->handle();

    $message = trans('data_transfer::app.job.stopped', ['count' => $failValidation ? 2 : 1]);

    expect($message)->not->toBe('data_transfer::app.job.stopped')
        ->and(File::get(storage_path(JobLogger::getJobLogPath($track->id))))->toContain('ERROR: '.$message);
})->with('stopping validations');

it('does not let already queued batch and completion jobs revive a failed track', function (string $strategy, int $allowedErrors, bool $failValidation): void {
    StopOnErrorsTestImporter::$failValidation = $failValidation;
    $track = makeStopOnErrorsTrack($strategy, $allowedErrors);
    (new ImportTrackBatch($track))->handle();
    $track->refresh();

    (new ImportBatch($track->batches->first(), $track->id))->handle();
    (new Completed($track, $track->id))->handle();

    expect($track->fresh()->state)->toBe(Import::STATE_FAILED)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([]);
    Event::assertNotDispatched('data_transfer.imports.started');
    Event::assertNotDispatched('data_transfer.imports.completed');
})->with('stopping validations');

it('does not revive a failed track from a duplicate delivery', function (string $strategy, int $allowedErrors, bool $failValidation): void {
    StopOnErrorsTestImporter::$failValidation = $failValidation;
    $track = makeStopOnErrorsTrack($strategy, $allowedErrors);
    $staleDuplicate = $track->fresh();
    (new ImportTrackBatch($track))->handle();
    $failedAt = $track->fresh()->completed_at;

    StopOnErrorsTestImporter::$failValidation = false;
    StopOnErrorsTestImporter::$rows = [['code' => 'must-not-restart', 'status' => '1']];
    (new ImportTrackBatch($staleDuplicate))->handle();

    $track->refresh();
    expect($track->state)->toBe(Import::STATE_FAILED)
        ->and($track->completed_at)->toEqual($failedAt)
        ->and($track->errors_count)->toBe($failValidation ? 2 : 1)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([]);
})->with('stopping validations');

it('does not restart a track that has already reached a terminal state', function (string $state): void {
    $track = makeStopOnErrorsTrack(Import::VALIDATION_STRATEGY_SKIP_ERRORS, 10, $state);
    $updatedAt = $track->updated_at;

    (new ImportTrackBatch($track))->handle();

    $track->refresh();
    expect($track->state)->toBe($state)
        ->and($track->updated_at)->toEqual($updatedAt)
        ->and($track->batches)->toHaveCount(0)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([]);
    Event::assertNotDispatched('data_transfer.imports.started');
    Event::assertNotDispatched('data_transfer.imports.completed');
})->with([Import::STATE_CANCELLED, Import::STATE_COMPLETED, Import::STATE_FAILED]);

it('does not finalize failed validation without pending batches', function (bool $failValidation): void {
    StopOnErrorsTestImporter::$rows = [['code' => 'invalid-only', 'status' => 'invalid']];
    StopOnErrorsTestImporter::$failValidation = $failValidation;
    $track = makeStopOnErrorsTrack(Import::VALIDATION_STRATEGY_STOP_ON_ERROR, 0);

    (new ImportTrackBatch($track))->handle();

    expect($track->fresh()->batches)->toHaveCount(0)
        ->and($track->fresh()->state)->toBe(Import::STATE_FAILED)
        ->and($track->fresh()->completed_at)->not->toBeNull()
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([]);
    Event::assertNotDispatched('data_transfer.imports.started');
    Event::assertNotDispatched('data_transfer.imports.completed');
})->with([false, true]);

it('executes permitted pending batches and completes the import', function (string $strategy, int $allowedErrors, bool $invalidRow): void {
    if (! $invalidRow) {
        StopOnErrorsTestImporter::$rows = array_values(array_filter(
            StopOnErrorsTestImporter::$rows,
            fn (array $row): bool => $row['status'] !== 'invalid'
        ));
    }
    $track = makeStopOnErrorsTrack($strategy, $allowedErrors);

    (new ImportTrackBatch($track))->handle();

    $track->refresh();
    expect(StopOnErrorsTestImporter::$importedCodes)->toBe(['valid-one', 'valid-two'])
        ->and($track->state)->toBe(Import::STATE_COMPLETED)
        ->and($track->completed_at)->not->toBeNull()
        ->and($track->errors_count)->toBe($invalidRow ? 1 : 0)
        ->and($track->summary['updated'])->toEqual(2);
    Event::assertDispatchedTimes('data_transfer.imports.started', 1);
    Event::assertDispatchedTimes('data_transfer.imports.completed', 1);
})->with([
    'valid stop-on-errors import'              => [Import::VALIDATION_STRATEGY_STOP_ON_ERROR, 0, false],
    'skip-errors below allowance'              => [Import::VALIDATION_STRATEGY_SKIP_ERRORS, 2, true],
    'skip-errors ignores stop-on-errors limit' => [Import::VALIDATION_STRATEGY_SKIP_ERRORS, 0, true],
]);

it('completes a skip-errors import whose rows are all invalid with a completion time', function (): void {
    StopOnErrorsTestImporter::$rows = [['code' => 'invalid-only', 'status' => 'invalid']];
    $track = makeStopOnErrorsTrack(Import::VALIDATION_STRATEGY_SKIP_ERRORS, 10);

    (new ImportTrackBatch($track))->handle();

    $track->refresh();
    expect($track->batches)->toHaveCount(0)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([])
        ->and($track->state)->toBe(Import::STATE_COMPLETED)
        ->and($track->completed_at)->not->toBeNull()
        ->and($track->errors_count)->toBe(1)
        ->and($track->summary)->toEqual(['created' => 0, 'updated' => 0, 'deleted' => 0]);
    Event::assertNotDispatched('data_transfer.imports.started');
    Event::assertDispatchedTimes('data_transfer.imports.completed', 1);
});
