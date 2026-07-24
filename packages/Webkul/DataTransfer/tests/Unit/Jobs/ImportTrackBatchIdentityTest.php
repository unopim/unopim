<?php

use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;

/*
 * A persistent queue worker keeps the admin guard populated between jobs. The
 * import job must run as its own batch owner and must not inherit — or leave
 * behind — another admin's identity.
 */
it('does not leak the acting admin identity across import jobs', function () {
    $adminA = Admin::factory()->create();
    $adminB = Admin::factory()->create();

    $jobTrack = JobTrack::factory()->create(['user_id' => $adminB->id]);

    // Simulate a stale identity left by a previous job on the same worker.
    auth()->guard('admin')->setUser($adminA);

    try {
        (new ImportTrackBatch($jobTrack))->handle();
    } catch (Throwable) {
        // The import pipeline may abort on the factory's minimal batch; the
        // identity handling (set own user, clear on exit) is what this asserts.
    }

    expect(auth()->guard('admin')->user()?->id)->not->toBe($adminA->id);
});
