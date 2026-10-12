<?php

use Webkul\DataTransfer\Helpers\AbstractJob;
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
        'job_instances_id'    => $instanceId,
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => 'stop-on-errors',
        'meta'                => json_encode([]),
        'state'               => $state,
        'heartbeat_at'        => $heartbeatAt,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);
}

it('fails when the runtime php version is below the requirement', function () {
    config(['upgrade.minimum_php' => '99.0.0']);

    $check = preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.php-version')
    );

    expect($check->status)->toBe(CheckStatus::Failed)
        ->and($check->detail)->toContain(PHP_VERSION)
        ->and($check->remedy)->toContain('99.0.0');
});

it('passes when the runtime php version satisfies the requirement', function () {
    config(['upgrade.minimum_php' => '8.0.0']);

    expect(preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.php-version')
    )->status)->toBe(CheckStatus::Passed);
});

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
    /**
     * A state no real job uses, so the assertion measures only the row this
     * test inserted rather than whatever the database already holds.
     */
    config(['upgrade.active_job_states' => ['upgrade_test_active']]);

    trackJob('upgrade_test_active');

    $check = preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.active-jobs')
    );

    expect($check->status)->toBe(CheckStatus::Failed);
});

it('ignores tracked jobs whose heartbeat has gone stale', function () {
    config(['upgrade.active_job_states' => ['upgrade_test_active'], 'upgrade.stale_job_minutes' => 15]);

    trackJob('upgrade_test_active', now()->subHour()->toDateTimeString());

    expect(preflightResult(
        app(PreflightChecker::class)->run(),
        trans('installer::app.upgrade.checks.active-jobs')
    )->status)->toBe(CheckStatus::Passed);
});

/**
 * The configured job states are plain strings so the config file does not
 * depend on another package at merge time. This keeps them honest: a rename in
 * DataTransfer must fail here rather than silently disarm the gate.
 */
it('keeps the configured active job states in step with the job class', function () {
    expect(config('upgrade.active_job_states'))->toBe([
        AbstractJob::STATE_PENDING,
        AbstractJob::STATE_VALIDATED,
        AbstractJob::STATE_PROCESSING,
        AbstractJob::STATE_LINKING,
        AbstractJob::STATE_INDEXING,
    ]);
});
