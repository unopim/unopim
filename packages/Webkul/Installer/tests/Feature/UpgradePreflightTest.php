<?php

use Webkul\Installer\Helpers\Upgrade\CheckStatus;
use Webkul\Installer\Helpers\Upgrade\MigrationInspector;
use Webkul\Installer\Helpers\Upgrade\PreflightChecker;

/**
 * Preflight decides whether the upgrade is allowed to touch anything at all, so
 * each gate is asserted independently of the console layer.
 */
function preflightResult(array $results, string $name): ?object
{
    return collect($results)->firstWhere('name', $name);
}

function trackJob(string $state, ?string $heartbeatAt = null): void
{
    $instanceId = DB::table('job_instances')->insertGetId([
        'code'                => 'upgrade-test-'.uniqid(),
        'entity_type'         => 'product',
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => 'stop-on-errors',
        'field_separator'     => ',',
        'file_path'           => 'imports/upgrade-test.csv',
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    DB::table('job_track')->insert([
        'job_instances_id' => $instanceId,
        'type'             => 'import',
        'state'            => $state,
        'heartbeat_at'     => $heartbeatAt,
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);
}

it('fails when the installed release predates the supported floor', function () {
    config(['upgrade.source_version_sentinel' => 'a_migration_that_never_ran']);

    $results = app(PreflightChecker::class)->run();

    $check = preflightResult($results, trans('installer::app.upgrade.checks.source-version'));

    expect($check->status)->toBe(CheckStatus::Failed)
        ->and($check->remedy)->not->toBe('');
});

it('downgrades the version gate to a warning when forced', function () {
    config(['upgrade.source_version_sentinel' => 'a_migration_that_never_ran']);

    $results = app(PreflightChecker::class)->run(ignoreSourceVersion: true);

    expect(preflightResult($results, trans('installer::app.upgrade.checks.source-version'))->status)
        ->toBe(CheckStatus::Warning);
});

it('passes the version gate when the sentinel migration has run', function () {
    $ran = app(MigrationInspector::class)->ran();

    expect($ran)->not->toBeEmpty();

    config(['upgrade.source_version_sentinel' => $ran[0]]);

    $results = app(PreflightChecker::class)->run();

    expect(preflightResult($results, trans('installer::app.upgrade.checks.source-version'))->status)
        ->toBe(CheckStatus::Passed);
});

it('fails when a required php extension is missing', function () {
    config(['upgrade.required_extensions' => ['an_extension_that_does_not_exist']]);

    $check = preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.extensions')
    );

    expect($check->status)->toBe(CheckStatus::Failed)
        ->and($check->detail)->toContain('an_extension_that_does_not_exist');
});

it('fails when a required directory is not writable', function () {
    config(['upgrade.writable_paths' => ['a/path/that/does/not/exist']]);

    expect(preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.writable-paths')
    )->status)->toBe(CheckStatus::Failed);
});

it('fails when tracked import or export jobs are still running', function () {
    config(['upgrade.active_job_states' => ['processing']]);

    trackJob('processing');

    $check = preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.active-jobs')
    );

    expect($check->status)->toBe(CheckStatus::Failed);
});

it('ignores tracked jobs whose heartbeat has gone stale', function () {
    config(['upgrade.active_job_states' => ['processing'], 'upgrade.stale_job_minutes' => 15]);

    trackJob('processing', now()->subHour()->toDateTimeString());

    expect(preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.active-jobs')
    )->status)->toBe(CheckStatus::Passed);
});
