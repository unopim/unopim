<?php

use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

function trackedJob(string $type): array
{
    $jobInstance = JobInstances::create([
        'code'        => 'tracker-view-'.uniqid(),
        'entity_type' => 'products',
        'type'        => $type,
        'action'      => $type === 'export' ? 'export' : 'append',
        'file_path'   => $type.'s/test.csv',
    ]);

    $jobTrack = JobTrack::create([
        'state'            => Import::STATE_PROCESSING,
        'type'             => $type,
        'action'           => $type === 'export' ? 'export' : 'append',
        'file_path'        => $type.'s/test.csv',
        'meta'             => json_encode($jobInstance->toArray()),
        'job_instances_id' => $jobInstance->id,
        'user_id'          => auth('admin')->id(),
        'started_at'       => now(),
    ]);

    return [$jobInstance, $jobTrack];
}

describe('Job tracker view', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('renders the tracker with copy matching the job type', function (string $type, string $key) {
        [$jobInstance, $jobTrack] = trackedJob($type);

        $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))
            ->assertOk()
            ->assertSee(trans('admin::app.settings.data-transfer.tracker.'.$key), false);

        $jobInstance->delete();
    })->with([
        ['import', 'pause-failed'],
        ['export', 'pause-failed-export'],
        ['system', 'pause-failed'],
    ]);
});
