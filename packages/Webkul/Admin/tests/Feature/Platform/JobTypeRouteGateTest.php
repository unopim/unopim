<?php

use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);
});

dataset('gated import routes', [
    'import edit'   => ['get', 'admin.settings.data_transfer.imports.edit', 'instance'],
    'import view'   => ['get', 'admin.settings.data_transfer.imports.import-view', 'instance'],
    'import start'  => ['post', 'admin.settings.data_transfer.imports.start', 'track'],
    'tracker view'  => ['get', 'admin.settings.data_transfer.tracker.view', 'track'],
    'job pause'     => ['post', 'admin.settings.data_transfer.jobs.pause', 'track'],
    'job resume'    => ['post', 'admin.settings.data_transfer.jobs.resume', 'track'],
    'job cancel'    => ['post', 'admin.settings.data_transfer.jobs.cancel', 'track'],
    'legacy cancel' => ['post', 'admin.settings.data_transfer.imports.cancel', 'track'],
]);

dataset('gated export routes', [
    'export edit'  => ['get', 'admin.settings.data_transfer.exports.edit', 'instance'],
    'export view'  => ['get', 'admin.settings.data_transfer.exports.export-view', 'instance'],
    'export start' => ['post', 'admin.settings.data_transfer.exports.start', 'instance'],
    'tracker view' => ['get', 'admin.settings.data_transfer.tracker.view', 'track'],
    'job cancel'   => ['post', 'admin.settings.data_transfer.jobs.cancel', 'track'],
]);

it('responds 404 for an import job of a switched-off module', function (string $method, string $route, string $target) {
    $track = JobTrack::factory()->create();

    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $id = $target === 'track' ? $track->id : $track->job_instances_id;

    $this->json($method, route($route, $id))->assertNotFound();
})->with('gated import routes');

it('responds 404 for an export job of a switched-off module', function (string $method, string $route, string $target) {
    $track = JobTrack::factory()->export()->create();

    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $id = $target === 'track' ? $track->id : $track->job_instances_id;

    $this->json($method, route($route, $id))->assertNotFound();
})->with('gated export routes');

it('responds 404 for a job type tagged with a switched-off non-pim module', function (string $method, string $route, string $target) {
    config(['importers.records_items' => ['module' => 'records', 'title' => 'records']]);

    $track = JobTrack::factory()->create();

    JobInstances::query()->whereKey($track->job_instances_id)->update(['entity_type' => 'records_items']);

    $this->setPlatformModules('pim');

    $this->loginAsAdmin();

    $id = $target === 'track' ? $track->id : $track->job_instances_id;

    $this->json($method, route($route, $id))->assertNotFound();
})->with('gated import routes');

it('reaches the job routes of a visible job type', function (string $method, string $route, string $target) {
    $track = JobTrack::factory()->create();

    $this->setPlatformModules('pim');

    $this->loginAsAdmin();

    $id = $target === 'track' ? $track->id : $track->job_instances_id;

    expect($this->json($method, route($route, $id))->status())->not->toBe(404);
})->with('gated import routes');
