<?php

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webkul\Installer\Console\Commands\Upgrade;
use Webkul\Installer\Helpers\Upgrade\MigrationInspector;

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
    /**
     * Preflight reads live state, and a database that happens to carry a
     * running import would fail the run for reasons unrelated to what this
     * test asserts. Only the gates under test are left active.
     */
    config([
        'upgrade.active_job_states'       => [],
        'upgrade.source_version_sentinel' => app(MigrationInspector::class)->ran()[0],
    ]);

    $this->artisan('unopim:upgrade', ['--dry-run' => true])
        ->expectsOutputToContain(trans('installer::app.upgrade.dry-run-complete'))
        ->assertSuccessful();

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('registers the upgrade command', function () {
    expect(array_key_exists('unopim:upgrade', Artisan::all()))->toBeTrue();
});

/**
 * Artisan only propagates an exception for the errors that throw; a step that
 * merely returns a non-zero code would otherwise pass unnoticed and the site
 * would come back up on a half-migrated schema.
 */
function runUpgradeStep(string $command): void
{
    $upgrade = app(Upgrade::class);

    $upgrade->setLaravel(app());

    /**
     * `runStep` delegates to Command::call, which resolves the child command
     * through the console application rather than the container.
     */
    $upgrade->setApplication(
        Closure::bind(fn () => $this->getArtisan(), app(Kernel::class), Kernel::class)()
    );

    $output = new OutputStyle(new ArrayInput([]), new BufferedOutput);

    foreach (['input' => new ArrayInput([]), 'output' => $output] as $property => $value) {
        $reflected = new ReflectionProperty(Command::class, $property);
        $reflected->setValue($upgrade, $value);
    }

    $step = new ReflectionMethod(Upgrade::class, 'runStep');

    $step->invoke($upgrade, $command, []);
}

it('fails closed when a migration step returns a non-zero exit code', function () {
    Artisan::command('upgrade-test:fails', fn () => 1);

    expect(fn () => runUpgradeStep('upgrade-test:fails'))
        ->toThrow(RuntimeException::class, trans('installer::app.upgrade.step-failed', [
            'command' => 'upgrade-test:fails',
            'code'    => 1,
        ]));
});

it('accepts a migration step that succeeds', function () {
    Artisan::command('upgrade-test:succeeds', fn () => 0);

    runUpgradeStep('upgrade-test:succeeds');

    expect(true)->toBeTrue();
});
