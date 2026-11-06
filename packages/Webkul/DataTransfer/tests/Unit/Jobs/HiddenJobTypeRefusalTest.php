<?php

use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Export\ExportTrackBatch;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);

    $this->setPlatformModules('records');

    $this->disabledMessage = trans('data_transfer::app.job.module-disabled', ['type' => 'products']);
});

it('fails a queued import track whose job type was switched off', function () {
    $jobTrack = JobTrack::factory()->create();

    (new ImportTrackBatch($jobTrack))->handle();

    $jobTrack->refresh();

    expect($jobTrack->state)->toBe(Import::STATE_FAILED)
        ->and($jobTrack->errors)->toBe([$this->disabledMessage])
        ->and($jobTrack->started_at)->toBeNull();
});

it('fails a queued export track whose job type was switched off', function () {
    $jobTrack = JobTrack::factory()->export()->create();

    (new ExportTrackBatch($jobTrack))->handle();

    $jobTrack->refresh();

    expect($jobTrack->state)->toBe(Export::STATE_FAILED)
        ->and($jobTrack->errors)->toBe([$this->disabledMessage]);
});

it('refuses to execute a job instance whose type is switched off', function () {
    $admin = Admin::factory()->create();
    $jobInstance = JobInstances::factory()->exportJob()->create();

    $this->artisan('unopim:queue:work', ['jobId' => $jobInstance->id, 'userEmailId' => $admin->email])
        ->expectsOutputToContain($this->disabledMessage)
        ->assertExitCode(1);

    expect(JobTrack::query()->where('job_instances_id', $jobInstance->id)->exists())->toBeFalse();
});
