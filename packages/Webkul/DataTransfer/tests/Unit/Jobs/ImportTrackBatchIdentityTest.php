<?php

use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;

// A persistent worker keeps the admin guard populated between jobs, so the import must not inherit another identity.
it('does not leak the acting admin identity across import jobs', function () {
    $adminA = Admin::factory()->create();
    $adminB = Admin::factory()->create();

    $jobTrack = JobTrack::factory()->create(['user_id' => $adminB->id]);

    // Simulate a stale identity left by a previous job on the same worker.
    auth()->guard('admin')->setUser($adminA);

    try {
        (new ImportTrackBatch($jobTrack))->handle();
    } catch (Throwable) {
        // May abort on the factory's minimal batch; only the identity clear-on-exit is under test.
    }

    expect(auth()->guard('admin')->user()?->id)->not->toBe($adminA->id);
});
