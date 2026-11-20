<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

use function Pest\Laravel\get;

function trackerAclTrack(string $type, string $filePath): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'tracker_acl_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => $type,
        'action'              => $type,
        'validation_strategy' => 'skip',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_COMPLETED,
        'type'                => $type,
        'action'              => $type,
        'validation_strategy' => 'skip',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toJson(),
        'file_path'           => $filePath,
    ]);
}

beforeEach(function () {
    Storage::fake('private');

    Storage::disk('private')->put('ai-agent/exports/1/catalogue/products.csv', 'sku,name');
    Storage::disk('private')->put('imports/source/products.csv', 'sku,name');
});

it('blocks a tracker-only role from downloading an export track file', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    $track = trackerAclTrack('export', 'ai-agent/exports/1/catalogue/products.csv');

    get(route('admin.settings.data_transfer.tracker.download', $track->id))->assertForbidden();
});

it('blocks a tracker-only role from downloading an export track archive', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    $track = trackerAclTrack('export', 'ai-agent/exports/1/catalogue');

    get(route('admin.settings.data_transfer.tracker.archive.download', $track->id))->assertForbidden();
});

it('allows a role with export execute permission to download an export track file and archive', function () {
    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.job_tracker',
        'data_transfer.export',
        'data_transfer.export.execute',
    ]);

    $fileTrack = trackerAclTrack('export', 'ai-agent/exports/1/catalogue/products.csv');
    $folderTrack = trackerAclTrack('export', 'ai-agent/exports/1/catalogue');

    get(route('admin.settings.data_transfer.tracker.download', $fileTrack->id))->assertOk();

    get(route('admin.settings.data_transfer.tracker.archive.download', $folderTrack->id))->assertOk();
});

it('blocks a tracker-only role from downloading an import track file and archive', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    $fileTrack = trackerAclTrack('import', 'imports/source/products.csv');
    $folderTrack = trackerAclTrack('import', 'imports/source');

    get(route('admin.settings.data_transfer.tracker.download', $fileTrack->id))->assertForbidden();

    get(route('admin.settings.data_transfer.tracker.archive.download', $folderTrack->id))->assertForbidden();
});

it('allows a role with import execute permission to download an import track file and archive', function () {
    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.job_tracker',
        'data_transfer.imports',
        'data_transfer.imports.execute',
    ]);

    $fileTrack = trackerAclTrack('import', 'imports/source/products.csv');
    $folderTrack = trackerAclTrack('import', 'imports/source');

    get(route('admin.settings.data_transfer.tracker.download', $fileTrack->id))->assertOk();

    get(route('admin.settings.data_transfer.tracker.archive.download', $folderTrack->id))->assertOk();
});

it('does not let import execute download an import track whose job instance is an export', function () {
    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.job_tracker',
        'data_transfer.imports',
        'data_transfer.imports.execute',
    ]);

    $track = trackerAclTrack('import', 'ai-agent/exports/1/catalogue/products.csv');
    $track->jobInstance->update(['type' => 'export', 'action' => 'export']);

    get(route('admin.settings.data_transfer.tracker.download', $track->id))->assertForbidden();
});

it('requires import execute for a track whose type is unknown', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    $track = trackerAclTrack('import', 'imports/source/products.csv');
    $track->update(['type' => 'unknown']);

    get(route('admin.settings.data_transfer.tracker.download', $track->id))->assertForbidden();
});

it('shows the export download link on the tracker only with the execute permission of the track', function () {
    $track = trackerAclTrack('export', 'ai-agent/exports/1/catalogue/products.csv');
    $downloadUrl = route('admin.settings.data_transfer.tracker.download', $track->id);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker', 'data_transfer.export']);

    get(route('admin.settings.data_transfer.tracker.view', $track->id))->assertOk()->assertDontSee($downloadUrl, false);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker', 'data_transfer.export', 'data_transfer.export.execute']);

    get(route('admin.settings.data_transfer.tracker.view', $track->id))->assertOk()->assertSee($downloadUrl, false);
});

it('hides the export download link from import execute when the job instance is an export', function () {
    $track = trackerAclTrack('import', 'ai-agent/exports/1/catalogue/products.csv');
    $track->jobInstance->update(['type' => 'export', 'action' => 'export']);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker', 'data_transfer.imports', 'data_transfer.imports.execute']);

    get(route('admin.settings.data_transfer.tracker.view', $track->id))
        ->assertOk()
        ->assertDontSee(route('admin.settings.data_transfer.tracker.download', $track->id), false);
});

it('requires the execute permission to download a job file through the import and export routes', function (string $type, string $route, string $viewPermission, string $executePermission) {
    $track = trackerAclTrack($type, 'imports/source/products.csv');
    $track->jobInstance->update(['file_path' => 'imports/source/products.csv']);

    $this->loginWithPermissions(permissions: ['data_transfer', $viewPermission]);

    get(route($route, $track->jobInstance->id))->assertForbidden();

    $this->loginWithPermissions(permissions: ['data_transfer', $viewPermission, $executePermission]);

    get(route($route, $track->jobInstance->id))->assertOk();
})->with([
    'import' => ['import', 'admin.settings.data_transfer.imports.download', 'data_transfer.imports', 'data_transfer.imports.execute'],
    'export' => ['export', 'admin.settings.data_transfer.exports.download', 'data_transfer.export', 'data_transfer.export.execute'],
]);

it('requires import execute to download an import error report', function () {
    Storage::disk('private')->put('imports/errors/products.csv', 'sku,error');

    $track = trackerAclTrack('import', 'imports/source/products.csv');
    $track->update(['error_file_path' => 'imports/errors/products.csv']);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports']);

    get(route('admin.settings.data_transfer.imports.download_error_report', $track->id))->assertForbidden();

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports', 'data_transfer.imports.execute']);

    get(route('admin.settings.data_transfer.imports.download_error_report', $track->id))->assertOk();
});

it('shows the import error report link on the tracker only with import execute', function () {
    $track = trackerAclTrack('import', 'imports/source/products.csv');
    $track->update(['error_file_path' => 'imports/errors/products.csv']);
    $reportUrl = route('admin.settings.data_transfer.imports.download_error_report', $track->id);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker', 'data_transfer.imports']);

    get(route('admin.settings.data_transfer.tracker.view', $track->id))->assertOk()->assertDontSee($reportUrl, false);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker', 'data_transfer.imports', 'data_transfer.imports.execute']);

    get(route('admin.settings.data_transfer.tracker.view', $track->id))->assertOk()->assertSee($reportUrl, false);
});
