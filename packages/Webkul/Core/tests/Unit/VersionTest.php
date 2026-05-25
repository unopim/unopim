<?php

it('returns version string without v prefix', function () {
    $version = core()->version();

    expect($version)->not->toStartWith('v');
    expect($version)->toMatch('/^\d+\.\d+\.\d+$/');
});

it('version command outputs version without v prefix', function () {
    $this->artisan('unopim:version')
        ->expectsOutputToContain(core()->version())
        ->assertExitCode(0);

    // Ensure 'v' is not prepended to the version
    $version = core()->version();
    $this->artisan('unopim:version')
        ->doesntExpectOutputToContain('v'.$version);
});
