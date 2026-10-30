<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\Completed;
use Webkul\DataTransfer\Jobs\Import\ImportBatch;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Tests\Support\StopOnErrorsTestImporter;

beforeEach(function () {
    Storage::fake('private');
    // Production JSON grammars support MySQL/PostgreSQL; keep this regression runnable on SQLite.
    if (DB::getDriverName() === 'sqlite') {
        DB::macro('rawQueryGrammar', fn () => new class
        {
            public function jsonExtract(string $column, string $key): string
            {
                return "json_extract($column, '$.$key')";
            }
        });
    }
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

function makeStopOnErrorsTrack(string $strategy, int $allowedErrors): JobTrack
{
    $track = JobTrack::factory()->create([
        'validation_strategy' => $strategy,
        'allowed_errors'      => $allowedErrors,
    ]);
    $track->jobInstance->update(['entity_type' => 'stop_test']);

    return $track;
}

it('does not execute pending batches after validation must stop', function (string $strategy, int $allowedErrors, bool $failValidation) {
    StopOnErrorsTestImporter::$failValidation = $failValidation;
    $track = makeStopOnErrorsTrack($strategy, $allowedErrors);
    $duplicateDelivery = $track->fresh();

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

    // Already queued work and finalizers must not revive a failed track.
    (new ImportBatch($track->batches->first(), $track->id))->handle();
    (new Completed($track, $track->id))->handle();

    expect($track->fresh()->state)->toBe(Import::STATE_FAILED)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([]);
    Event::assertNotDispatched('data_transfer.imports.started');
    Event::assertNotDispatched('data_transfer.imports.completed');

    // A duplicate delivery of the tracker must preserve failure without revalidating or importing.
    StopOnErrorsTestImporter::$failValidation = false;
    StopOnErrorsTestImporter::$rows = [['code' => 'must-not-restart', 'status' => '1']];
    $failedAt = $track->fresh()->completed_at;
    (new ImportTrackBatch($duplicateDelivery))->handle();

    expect($track->fresh()->state)->toBe(Import::STATE_FAILED)
        ->and($track->fresh()->completed_at)->toEqual($failedAt)
        ->and($track->fresh()->errors_count)->toBe($failValidation ? 2 : 1)
        ->and(StopOnErrorsTestImporter::$importedCodes)->toBe([]);
})->with([
    'stop on first error'                        => [Import::VALIDATION_STRATEGY_STOP_ON_ERROR, 0, false],
    'validation exception even with skip errors' => [Import::VALIDATION_STRATEGY_SKIP_ERRORS, 10, true],
]);

it('does not finalize failed validation without pending batches', function (bool $failValidation) {
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

it('executes permitted pending batches and completes the import', function (string $strategy, int $allowedErrors, bool $invalidRow) {
    if (! $invalidRow) {
        StopOnErrorsTestImporter::$rows = array_values(array_filter(
            StopOnErrorsTestImporter::$rows,
            fn (array $row) => $row['status'] !== 'invalid'
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
    'stop-on-errors at allowed limit'          => [Import::VALIDATION_STRATEGY_STOP_ON_ERROR, 1, true],
    'skip-errors below allowance'              => [Import::VALIDATION_STRATEGY_SKIP_ERRORS, 2, true],
    'skip-errors ignores stop-on-errors limit' => [Import::VALIDATION_STRATEGY_SKIP_ERRORS, 0, true],
]);
