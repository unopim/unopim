<?php

use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;

/*
 * A worker killed by OOM or SIGKILL never reaches its failure handler, so without
 * this reconcile the tracker screen polls an active state forever and the run
 * looks like it is still going.
 */

function stalledTrack(array $attributes = []): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'stalled_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create(array_merge([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toJson(),
        'heartbeat_at'        => now()->subHour(),
    ], $attributes));
}

function actAsDataTransferAdmin(): Admin
{
    $role = Role::factory()->create(['permission_type' => 'all']);

    $admin = Admin::factory()->create(['role_id' => $role->id]);

    test()->actingAs($admin, 'admin');

    return $admin;
}

it('reports a job whose worker died as failed instead of still running', function () {
    actAsDataTransferAdmin();

    $track = stalledTrack();

    get(route('admin.settings.data_transfer.tracker.view', $track->id))->assertOk();

    $track->refresh();

    expect($track->state)->toBe(Export::STATE_FAILED);
    expect($track->errors)->not->toBeEmpty();
});

it('leaves a job that is still reporting in running', function () {
    actAsDataTransferAdmin();

    $track = stalledTrack(['heartbeat_at' => now()->subSeconds(10)]);

    get(route('admin.settings.data_transfer.tracker.view', $track->id))->assertOk();

    expect($track->fresh()->state)->toBe(Export::STATE_PROCESSING);
});

it('fails a stalled job from the polled stats endpoint so the page stops spinning', function () {
    actAsDataTransferAdmin();

    $track = stalledTrack();

    get(route('admin.settings.data_transfer.exports.stats', ['id' => $track->id]))
        ->assertOk()
        ->assertJsonPath('export.state', Export::STATE_FAILED);
});
