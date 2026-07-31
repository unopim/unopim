<?php

use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

function gridActionIndices(string $route): array
{
    $response = test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route($route))
        ->assertOk();

    return array_column($response->json('actions') ?? [], 'index');
}

function trackFor(JobInstances $jobInstance): JobTrack
{
    return JobTrack::create([
        'state'               => Import::STATE_PROCESSING,
        'type'                => $jobInstance->type,
        'action'              => $jobInstance->action,
        'file_path'           => $jobInstance->file_path,
        'validation_strategy' => $jobInstance->validation_strategy ?? '',
        'meta'                => $jobInstance->toArray(),
        'job_instances_id'    => $jobInstance->id,
        'user_id'             => auth('admin')->id(),
        'started_at'          => now(),
    ]);
}

it('hides the edit action on the export profile grid when the role withholds edit', function () {
    JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.export',
        'data_transfer.export.delete',
    ]);

    expect(gridActionIndices('admin.settings.data_transfer.exports.index'))
        ->not->toContain('edit')
        ->toContain('delete');
});

it('shows the edit action on the export profile grid when the role grants edit', function () {
    JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.export',
        'data_transfer.export.edit',
    ]);

    expect(gridActionIndices('admin.settings.data_transfer.exports.index'))->toContain('edit');
});

it('hides the edit action on the import profile grid when the role withholds edit', function () {
    JobInstances::factory()->importJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.imports',
        'data_transfer.imports.delete',
    ]);

    expect(gridActionIndices('admin.settings.data_transfer.imports.index'))
        ->not->toContain('edit')
        ->toContain('delete');
});

it('shows the edit action on the import profile grid when the role grants edit', function () {
    JobInstances::factory()->importJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.imports',
        'data_transfer.imports.edit',
    ]);

    expect(gridActionIndices('admin.settings.data_transfer.imports.index'))->toContain('edit');
});

it('hides the edit button on the job tracker page when the role withholds edit', function () {
    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.job_tracker',
    ]);

    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    $track = trackFor($export);

    $this->get(route('admin.settings.data_transfer.tracker.view', $track->id))
        ->assertOk()
        ->assertDontSee(route('admin.settings.data_transfer.exports.edit', $export->id));
});

it('shows the edit button on the job tracker page when the role grants edit', function () {
    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.job_tracker',
        'data_transfer.export.edit',
    ]);

    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    $track = trackFor($export);

    $this->get(route('admin.settings.data_transfer.tracker.view', $track->id))
        ->assertOk()
        ->assertSee(route('admin.settings.data_transfer.exports.edit', $export->id));
});

it('hides the edit button on the import job tracker page when the role withholds edit', function () {
    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.job_tracker',
    ]);

    $import = JobInstances::factory()->importJob()->entityProduct()->create();

    $track = trackFor($import);

    $this->get(route('admin.settings.data_transfer.tracker.view', $track->id))
        ->assertOk()
        ->assertDontSee(route('admin.settings.data_transfer.imports.edit', $import->id));
});
