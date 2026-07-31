<?php

use Illuminate\Support\Facades\Bus;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\JobLogger;

function createFilelessProductJobInstance(): JobInstances
{
    return JobInstances::create([
        'code'                => 'bulk_product_update',
        'entity_type'         => 'products',
        'type'                => 'system',
        'action'              => 'update',
        'validation_strategy' => 'skip-errors',
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => null,
    ]);
}

function createRerunTrack(JobInstances $jobInstance): JobTrack
{
    return JobTrack::create([
        'state'               => Import::STATE_PENDING,
        'type'                => 'import',
        'action'              => 'update',
        'validation_strategy' => 'skip-errors',
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'file_path'           => $jobInstance->file_path,
        'meta'                => json_encode($jobInstance->toArray()),
        'job_instances_id'    => $jobInstance->id,
        'user_id'             => auth('admin')->id(),
        'started_at'          => now(),
    ]);
}

beforeEach(function () {
    $this->loginAsAdmin();
});

it('validate() fails gracefully instead of a fatal error when the import source file is missing', function () {
    $jobInstance = createFilelessProductJobInstance();
    $jobTrack = createRerunTrack($jobInstance);

    app(Import::class)
        ->setImport($jobTrack)
        ->setLogger(JobLogger::make($jobTrack->id))
        ->validate();

    $jobTrack->refresh();

    expect($jobTrack->state)->toBe(Import::STATE_FAILED)
        ->and($jobTrack->errors)->not->toBeNull();
});

it('does not let a fileless system job (bulk_product_update) be re-run via importNow', function () {
    Bus::fake();

    $jobInstance = createFilelessProductJobInstance();

    $response = $this->put(
        route('admin.settings.data_transfer.imports.import_now', $jobInstance->id)
    );

    expect(JobTrack::where('job_instances_id', $jobInstance->id)->count())->toBe(0);

    Bus::assertNotDispatched(ImportTrackBatch::class);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
