<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

function exportedJob(array $files): array
{
    $jobInstance = JobInstances::create([
        'code'                => 'archive-'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'file_path'           => 'exports/archive.csv',
    ]);

    /**
     * Unique per call rather than keyed by the job instance id: ids come from a
     * sequence that keeps climbing while the rows roll back, so a run walks over
     * export folders earlier runs left on the private disk. Landing on one that
     * still holds files makes an export seeded as empty archive successfully.
     */
    $folder = 'exports/test-'.uniqid().'/uno-pim';

    foreach ($files as $name => $contents) {
        Storage::disk('private')->put($folder.'/'.$name, $contents);
    }

    $jobTrack = JobTrack::create([
        'state'               => Export::STATE_COMPLETED,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'file_path'           => $folder,
        'meta'                => json_encode($jobInstance->toArray()),
        'job_instances_id'    => $jobInstance->id,
        'user_id'             => auth('admin')->id(),
        'started_at'          => now(),
        'completed_at'        => now(),
    ]);

    return [$jobInstance, $jobTrack, $folder];
}

describe('Job tracker archive download', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('archives the exported files from the private disk', function () {
        [$jobInstance, $jobTrack, $folder] = exportedJob([
            'products.csv' => "sku,name\nBATT-1,Battery\n",
        ]);

        $response = $this->get(route('admin.settings.data_transfer.tracker.archive.download', $jobTrack->id));

        $response->assertOk();
        expect($response->headers->get('content-disposition'))->toContain('.zip');

        Storage::disk('private')->deleteDirectory($folder);
        $jobInstance->delete();
    });

    it('reports an empty export instead of failing on a missing archive', function () {
        [$jobInstance, $jobTrack, $folder] = exportedJob([]);

        expect(Storage::disk('private')->allFiles($folder))->toBeEmpty();

        $this->get(route('admin.settings.data_transfer.tracker.archive.download', $jobTrack->id))
            ->assertNotFound();

        $jobInstance->delete();
    });

    it('archives a job whose file path is the export folder', function () {
        [$jobInstance, $jobTrack, $folder] = exportedJob([
            'products.csv' => "sku,name\nBATT-1,Battery\n",
        ]);

        $response = $this->get(route('admin.settings.data_transfer.tracker.download', $jobTrack->id));

        $response->assertOk();
        expect($response->headers->get('content-disposition'))->toContain('.zip');

        Storage::disk('private')->deleteDirectory($folder);
        $jobInstance->delete();
    });

    it('downloads a job whose file path is a single file', function () {
        [$jobInstance, $jobTrack, $folder] = exportedJob([
            'products.csv' => "sku,name\nBATT-1,Battery\n",
        ]);

        $jobTrack->update(['file_path' => $folder.'/products.csv']);

        $this->get(route('admin.settings.data_transfer.tracker.download', $jobTrack->id))
            ->assertOk()
            ->assertDownload('products.csv');

        Storage::disk('private')->deleteDirectory($folder);
        $jobInstance->delete();
    });
});
