<?php

use Illuminate\Support\Facades\Event;
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

    it('renders the tracker with copy matching the job type', function (string $type, string $key, string $view) {
        [$jobInstance, $jobTrack] = trackedJob($type);

        $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))
            ->assertOk()
            ->assertViewIs($view)
            ->assertSee(trans('admin::app.settings.data-transfer.tracker.'.$key), false);

        $jobInstance->delete();
    })->with([
        ['import', 'pause-failed', 'admin::settings.data-transfer.tracker.import'],
        ['export', 'pause-failed-export', 'admin::settings.data-transfer.tracker.export'],
        ['system', 'pause-failed', 'admin::settings.data-transfer.tracker.import'],
    ]);

    it('renders every job state branch through the shared component', function () {
        [$jobInstance, $jobTrack] = trackedJob('import');

        $response = $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))->assertOk();

        $response->assertSee('<v-job-tracker', false);
        $response->assertSee('id="v-job-tracker-template"', false);

        foreach ([
            'pending', 'validating', 'validated', 'failed', 'paused',
            'cancelled', 'processing', 'linking', 'indexing', 'completed',
        ] as $state) {
            $response->assertSee("importResource.state == '".$state."'", false);
        }

        $jobInstance->delete();
    });

    it('points the job actions at the neutral job routes', function () {
        [$jobInstance, $jobTrack] = trackedJob('export');

        $encoded = fn (string $route): string => trim(json_encode(route($route, $jobTrack->id)), '"');

        $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))
            ->assertOk()
            ->assertSee($encoded('admin.settings.data_transfer.jobs.pause'), false)
            ->assertSee($encoded('admin.settings.data_transfer.jobs.stats'), false);

        $jobInstance->delete();
    });

    it('exposes render events extensions can hook into', function () {
        $captured = [];

        Event::listen('unopim.admin.settings.data_transfer.tracker.job.state.paused.before', function () use (&$captured) {
            $captured[] = 'paused.before';
        });

        Event::listen('unopim.admin.settings.data_transfer.tracker.job.before', function () use (&$captured) {
            $captured[] = 'job.before';
        });

        [$jobInstance, $jobTrack] = trackedJob('import');

        $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))->assertOk();

        expect($captured)->toContain('job.before')->toContain('paused.before');

        $jobInstance->delete();
    });
});
