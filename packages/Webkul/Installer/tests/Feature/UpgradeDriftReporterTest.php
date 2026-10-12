<?php

use Illuminate\Support\Facades\File;
use Webkul\Installer\Helpers\Upgrade\DriftReporter;

/**
 * The drift report is the only thing standing between an operator and silently
 * losing their own configuration, so it must name every file they still own.
 */
beforeEach(function () {
    $this->previous = sys_get_temp_dir().'/unopim-upgrade-drift-'.uniqid();

    File::ensureDirectoryExists($this->previous.'/config');
});

afterEach(function () {
    File::deleteDirectory($this->previous);
});

it('reports environment keys the release added and retired', function () {
    File::put($this->previous.'/.env', "APP_NAME=UnoPim\nRESPONSE_CACHE_ENABLED=false\n");

    config(['upgrade.removed_env_keys' => ['RESPONSE_CACHE_ENABLED']]);

    $report = app(DriftReporter::class)->from($this->previous)->report();

    expect($report['env']['removed'])->toContain('RESPONSE_CACHE_ENABLED');

    $shipped = collect(preg_split('/\R/', File::get(base_path('.env.example'))))
        ->map(fn (string $line): string => trim($line))
        ->filter(fn (string $line): bool => preg_match('/^[A-Z0-9_]+\s*=/', $line) === 1)
        ->isNotEmpty();

    expect($shipped)->toBeTrue()
        ->and($report['env']['missing'])->not->toContain('APP_NAME');
});

it('reports composer requirements present only in the previous release', function () {
    File::put($this->previous.'/.env', "APP_NAME=UnoPim\n");

    File::put($this->previous.'/composer.json', json_encode([
        'require' => [
            'php'                    => '^8.4.1',
            'acme/custom-connector'  => '^1.0',
        ],
    ]));

    $report = app(DriftReporter::class)->from($this->previous)->report();

    expect($report['composer'])->toContain('acme/custom-connector')
        ->and($report['composer'])->not->toContain('php');
});

it('reports configuration files that differ from the previous release', function () {
    File::put($this->previous.'/.env', "APP_NAME=UnoPim\n");

    File::put($this->previous.'/config/app.php', "<?php\n\nreturn ['name' => 'Custom'];\n");

    $report = app(DriftReporter::class)->from($this->previous)->report();

    expect($report['config'])->toContain('config/app.php');
});

it('skips configuration and composer comparison without a previous release', function () {
    $report = app(DriftReporter::class)->from(null)->report();

    expect($report['compared'])->toBeFalse()
        ->and($report['config'])->toBe([])
        ->and($report['composer'])->toBe([]);
});
