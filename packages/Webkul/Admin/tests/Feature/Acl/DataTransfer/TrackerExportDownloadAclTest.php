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

it('keeps import track downloads available to a tracker-only role', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    $fileTrack = trackerAclTrack('import', 'imports/source/products.csv');
    $folderTrack = trackerAclTrack('import', 'imports/source');

    get(route('admin.settings.data_transfer.tracker.download', $fileTrack->id))->assertOk();

    get(route('admin.settings.data_transfer.tracker.archive.download', $folderTrack->id))->assertOk();
});
