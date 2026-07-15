<?php

/*
 * These routes previously ran with no permission check, so any authenticated
 * admin could download arbitrary import source/log/error files or drive an
 * import (start/validate/link/stats). Each now checks the same permission its
 * sibling pages use; an admin without it must get a 403.
 */

use function Pest\Laravel\get;

it('blocks tracker file download without the job_tracker permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.tracker.download', 1))->assertStatus(403);
});

it('blocks tracker archive download without the job_tracker permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.tracker.archive.download', 1))->assertStatus(403);
});

it('blocks tracker log download without the job_tracker permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.tracker.log.download', 1))->assertStatus(403);
});

it('blocks starting an import without the imports.execute permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.imports.start', 1))->assertStatus(403);
});

it('blocks validating an import without the imports.execute permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.imports.validate', 1))->assertStatus(403);
});

it('blocks import source download without the imports permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.imports.download', 1))->assertStatus(403);
});

it('blocks import error-report download without the imports permission', function () {
    $this->loginWithPermissions();

    get(route('admin.settings.data_transfer.imports.download_error_report', 1))->assertStatus(403);
});

it('allows tracker log download when the job_tracker permission is granted', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    // Permission passes the guard; a missing job id then yields 404, never 403.
    get(route('admin.settings.data_transfer.tracker.log.download', 999999))->assertStatus(404);
});
