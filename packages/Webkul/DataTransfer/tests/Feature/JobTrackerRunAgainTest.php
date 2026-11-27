<?php

use Webkul\DataTransfer\Models\JobTrack;

/**
 * Create a completed job track of the given type with a job instance of the same type.
 */
function runAgainCompletedTrack(string $type): JobTrack
{
    $jobTrack = $type === 'export'
        ? JobTrack::factory()->export()->completed()->create()
        : JobTrack::factory()->completed()->create();

    if ($type === 'system') {
        $jobTrack->jobInstance()->update(['type' => 'system']);

        $jobTrack->update(['type' => 'system']);
    }

    return $jobTrack;
}

/**
 * Render the tracker page of a job track for an admin holding the given permissions.
 *
 * @param  list<string>|null  $permissions
 */
function runAgainTrackerPage(JobTrack $jobTrack, ?array $permissions = null): string
{
    if ($permissions === null) {
        test()->loginAsAdmin();
    } else {
        test()->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker', ...$permissions]);
    }

    return test()->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))
        ->assertOk()
        ->getContent();
}

/**
 * Return the Run Again button label.
 */
function runAgainLabel(): string
{
    return trans('admin::app.settings.data-transfer.tracker.run-again');
}

describe('Run Again on a completed job', function (): void {
    it('links a completed job to its own job page for a full-access admin', function (string $type, string $route, string $otherRoute): void {
        $jobTrack = runAgainCompletedTrack($type);

        expect(runAgainTrackerPage($jobTrack))
            ->toContain(runAgainLabel())
            ->toContain(route($route, $jobTrack->job_instances_id))
            ->not->toContain(route($otherRoute, $jobTrack->job_instances_id));
    })->with([
        'export' => ['export', 'admin.settings.data_transfer.exports.export-view', 'admin.settings.data_transfer.imports.import-view'],
        'import' => ['import', 'admin.settings.data_transfer.imports.import-view', 'admin.settings.data_transfer.exports.export-view'],
    ]);

    it('hides Run Again on a system job, which has no job page', function (): void {
        $jobTrack = runAgainCompletedTrack('system');

        expect(runAgainTrackerPage($jobTrack))
            ->not->toContain(runAgainLabel())
            ->not->toContain(route('admin.settings.data_transfer.imports.import-view', $jobTrack->job_instances_id))
            ->not->toContain(route('admin.settings.data_transfer.exports.export-view', $jobTrack->job_instances_id));
    });

    it('shows Run Again when the role can open and execute the job', function (string $type, array $permissions, string $route): void {
        $jobTrack = runAgainCompletedTrack($type);

        expect(runAgainTrackerPage($jobTrack, $permissions))
            ->toContain(runAgainLabel())
            ->toContain(route($route, $jobTrack->job_instances_id));
    })->with([
        'export' => ['export', ['data_transfer.export', 'data_transfer.export.execute'], 'admin.settings.data_transfer.exports.export-view'],
        'import' => ['import', ['data_transfer.imports', 'data_transfer.imports.execute'], 'admin.settings.data_transfer.imports.import-view'],
    ]);

    it('hides Run Again when the role cannot both open and execute the job', function (string $type, array $permissions, string $route): void {
        $jobTrack = runAgainCompletedTrack($type);

        expect(runAgainTrackerPage($jobTrack, $permissions))
            ->not->toContain(runAgainLabel())
            ->not->toContain(route($route, $jobTrack->job_instances_id));
    })->with([
        'export without execute'  => ['export', ['data_transfer.export'], 'admin.settings.data_transfer.exports.export-view'],
        'export without view'     => ['export', ['data_transfer.export.execute'], 'admin.settings.data_transfer.exports.export-view'],
        'import without execute'  => ['import', ['data_transfer.imports'], 'admin.settings.data_transfer.imports.import-view'],
        'import without view'     => ['import', ['data_transfer.imports.execute'], 'admin.settings.data_transfer.imports.import-view'],
        'export with import only' => ['export', ['data_transfer.imports', 'data_transfer.imports.execute'], 'admin.settings.data_transfer.exports.export-view'],
    ]);
});
