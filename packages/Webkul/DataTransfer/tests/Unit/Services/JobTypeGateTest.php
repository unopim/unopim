<?php

use Webkul\Core\Platform\Module;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\JobTypeGate;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config([
        'platform_modules.records' => ['name' => 'records', 'requires' => []],
        'importers'                => [
            'products'      => ['module' => 'pim'],
            'records'       => ['module' => 'records'],
            'enum_tagged'   => ['module' => Module::Pim],
            'extension_job' => [],
        ],
        'exporters' => [
            'products'      => ['module' => 'pim'],
            'extension_job' => [],
        ],
        'quick_exporters' => [
            'pim_quick' => ['module' => 'pim'],
            'csv'       => [],
        ],
    ]);
});

it('lists every registry the gate covers, quick exporters included', function () {
    expect(JobTypeGate::REGISTRIES)->toBe(['importers', 'exporters', 'quick_exporters']);
});

it('shows every job type while all their modules are on', function () {
    $this->setPlatformModules('pim,records');

    $gate = app(JobTypeGate::class);

    expect(array_keys($gate->visible('importers')))->toBe(['products', 'records', 'enum_tagged', 'extension_job'])
        ->and($gate->hidden('importers'))->toBe([])
        ->and($gate->hides('products'))->toBeFalse();
});

it('hides the job types of a switched-off module and keeps untagged ones', function () {
    $this->setPlatformModules('records');

    $gate = app(JobTypeGate::class);

    expect(array_keys($gate->visible('importers')))->toBe(['records', 'extension_job'])
        ->and($gate->hidden('importers'))->toBe(['products', 'enum_tagged'])
        ->and($gate->hidden('exporters'))->toBe(['products'])
        ->and($gate->hides('products'))->toBeTrue()
        ->and($gate->hides('enum_tagged'))->toBeTrue()
        ->and($gate->hides('records'))->toBeFalse()
        ->and($gate->hides('extension_job'))->toBeFalse()
        ->and($gate->hides('csv'))->toBeFalse();
});

it('hides a quick exporter type of a switched-off module', function () {
    $this->setPlatformModules('records');

    $gate = app(JobTypeGate::class);

    expect($gate->hidden('quick_exporters'))->toBe(['pim_quick'])
        ->and($gate->hides('pim_quick'))->toBeTrue();
});

it('never hides a type missing from the registries or an empty value', function (mixed $type) {
    $this->setPlatformModules('records');

    expect(app(JobTypeGate::class)->hides($type))->toBeFalse();
})->with(['uninstalled_extension', '', null, 42]);

it('hides a job track whose instance runs a hidden type', function () {
    $jobInstance = JobInstances::factory()->importJob()->entityProduct()->create();
    $jobTrack = JobTrack::factory()->create(['job_instances_id' => $jobInstance->id]);

    expect(app(JobTypeGate::class)->hidesTrack($jobTrack))->toBeFalse();

    $this->setPlatformModules('records');

    expect(app(JobTypeGate::class)->hidesTrack($jobTrack->fresh()))->toBeTrue();
});
