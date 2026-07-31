<?php

use Illuminate\Support\Carbon;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

function makeHeartbeatTrack(): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'heartbeat_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_PENDING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);
}

afterEach(fn () => Carbon::setTestNow());

it('records a heartbeat on the job track', function () {
    $track = makeHeartbeatTrack();

    expect($track->heartbeat_at)->toBeNull();

    app(Export::class)->setExport($track)->heartbeat();

    expect($track->fresh()->heartbeat_at)->not->toBeNull();
});

it('throttles repeat heartbeats to the configured interval', function () {
    config()->set('job_health.heartbeat_interval', 30);

    $track = makeHeartbeatTrack();
    $helper = app(Export::class)->setExport($track);

    Carbon::setTestNow('2026-07-31 10:00:00');
    $helper->heartbeat();

    $first = $track->fresh()->heartbeat_at;

    Carbon::setTestNow('2026-07-31 10:05:00');
    $helper->heartbeat();

    expect($track->fresh()->heartbeat_at->eq($first))->toBeTrue();
});

it('always writes when the heartbeat is forced', function () {
    config()->set('job_health.heartbeat_interval', 3600);

    $track = makeHeartbeatTrack();
    $helper = app(Export::class)->setExport($track);

    Carbon::setTestNow('2026-07-31 10:00:00');
    $helper->heartbeat();

    Carbon::setTestNow('2026-07-31 10:00:05');
    $helper->heartbeat(force: true);

    expect($track->fresh()->heartbeat_at->toDateTimeString())->toBe('2026-07-31 10:00:05');
});

it('marks the track alive when the export moves to a new state', function () {
    $track = makeHeartbeatTrack();

    app(Export::class)->setExport($track)->stateUpdate(Export::STATE_VALIDATED);

    expect($track->fresh()->heartbeat_at)->not->toBeNull();
});
