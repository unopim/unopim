<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;

function fileTrack(?string $filePath): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'archive_scope_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_COMPLETED,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'skip',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toJson(),
        'file_path'           => $filePath,
    ]);
}

beforeEach(function () {
    Storage::fake('private');

    Storage::disk('private')->put('imports/other-admin/source.csv', 'sku,name');

    $role = Role::factory()->create(['permission_type' => 'all']);

    $this->actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');
});

it('refuses to archive a track without a file path instead of zipping the whole disk', function (?string $filePath) {
    $track = fileTrack($filePath);

    get(route('admin.settings.data_transfer.tracker.archive.download', $track->id))->assertNotFound();

    get(route('admin.settings.data_transfer.tracker.download', $track->id))->assertNotFound();
})->with([
    'null'  => [null],
    'empty' => [''],
    'blank' => ['  '],
]);

it('archives only the files under the track folder', function () {
    Storage::disk('private')->put('exports/own/one.csv', 'a');

    $track = fileTrack('exports/own');

    $response = get(route('admin.settings.data_transfer.tracker.archive.download', $track->id))->assertOk();

    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());

    $names = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }

    $zip->close();

    expect($names)->toBe(['one.csv']);
});

it('links the job tracker heading back to the job tracker list', function () {
    $track = fileTrack('imports/other-admin/source.csv');

    get(route('admin.settings.data_transfer.tracker.view', $track->id))
        ->assertOk()
        ->assertSee('href="'.route('admin.settings.data_transfer.tracker.index').'"', false);
});
