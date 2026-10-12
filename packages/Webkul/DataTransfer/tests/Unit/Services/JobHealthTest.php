<?php

use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\JobHealth;

function makeHealthTrack(array $attributes = []): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'health_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create(array_merge([
        'state'               => Import::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
        'heartbeat_at'        => now()->subHour(),
    ], $attributes));
}

beforeEach(function (): void {
    config()->set('job_health.stall_timeout', 900);
});

it('treats a job whose heartbeat went quiet as stalled', function () {
    $track = makeHealthTrack(['heartbeat_at' => now()->subMinutes(30)]);

    expect(app(JobHealth::class)->isStalled($track))->toBeTrue();
});

it('leaves a job that reported in recently alone', function () {
    $track = makeHealthTrack(['heartbeat_at' => now()->subSeconds(60)]);

    expect(app(JobHealth::class)->isStalled($track))->toBeFalse();
});

it('never reaps a job that predates heartbeat tracking', function () {
    $track = makeHealthTrack(['heartbeat_at' => null]);

    expect(app(JobHealth::class)->isStalled($track))->toBeFalse();
    expect(app(JobHealth::class)->reap())->toBe(0);
});

it('leaves pending and paused jobs alone regardless of heartbeat age', function (string $state) {
    $track = makeHealthTrack([
        'state'        => $state,
        'heartbeat_at' => now()->subDay(),
    ]);

    expect(app(JobHealth::class)->isStalled($track))->toBeFalse();

    app(JobHealth::class)->reap();

    expect($track->fresh()->state)->toBe($state);
})->with([Import::STATE_PENDING, Import::STATE_PAUSED]);

it('leaves already finished jobs alone', function (string $state) {
    $track = makeHealthTrack([
        'state'        => $state,
        'heartbeat_at' => now()->subDay(),
    ]);

    app(JobHealth::class)->reap();

    expect($track->fresh()->state)->toBe($state);
})->with([Import::STATE_COMPLETED, Import::STATE_FAILED, Import::STATE_CANCELLED]);

it('fails a stalled job and records why', function () {
    $track = makeHealthTrack(['heartbeat_at' => now()->subMinutes(30)]);

    expect(app(JobHealth::class)->reap())->toBe(1);

    $track->refresh();

    expect($track->state)->toBe(Import::STATE_FAILED);
    expect($track->completed_at)->not->toBeNull();
    expect($track->errors)->toHaveCount(1);
    expect($track->errors[0])->toContain('15');
});

it('honours a configured stall timeout', function () {
    config()->set('job_health.stall_timeout', 7200);

    $track = makeHealthTrack(['heartbeat_at' => now()->subMinutes(30)]);

    expect(app(JobHealth::class)->isStalled($track))->toBeFalse();
});

it('reaps every stalled job in one pass', function () {
    makeHealthTrack(['heartbeat_at' => now()->subMinutes(30)]);
    makeHealthTrack(['heartbeat_at' => now()->subMinutes(45)]);
    makeHealthTrack(['heartbeat_at' => now()->subSeconds(30)]);

    expect(app(JobHealth::class)->reap())->toBe(2);
});

it('reaps stalled jobs from the scheduled command', function () {
    makeHealthTrack(['heartbeat_at' => now()->subMinutes(30)]);

    $this->artisan('unopim:data-transfer:reap-stalled')->assertSuccessful();

    expect(JobTrack::where('state', Import::STATE_FAILED)->count())->toBe(1);
});
