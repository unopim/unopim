<?php

use Illuminate\Support\Facades\Artisan;

/**
 * The command's contract: read-only until the operator confirms, and no
 * mutation at all once a preflight gate has failed.
 */
it('aborts before mutating anything when a preflight check fails', function () {
    config(['upgrade.required_extensions' => ['an_extension_that_does_not_exist']]);

    $this->artisan('unopim:upgrade')
        ->expectsOutputToContain(trans('installer::app.upgrade.phase.preflight'))
        ->assertFailed();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('does not reach the migration phase during a dry run', function () {
    $this->artisan('unopim:upgrade', ['--dry-run' => true])
        ->expectsOutputToContain(trans('installer::app.upgrade.dry-run-complete'))
        ->assertSuccessful();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('registers the upgrade command', function () {
    expect(array_key_exists('unopim:upgrade', Artisan::all()))->toBeTrue();
});
