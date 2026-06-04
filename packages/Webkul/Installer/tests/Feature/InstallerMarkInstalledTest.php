<?php

use Illuminate\Support\Facades\Event;
use Webkul\Installer\Console\Commands\Installer;

beforeEach(function () {
    $this->marker = tempnam(sys_get_temp_dir(), 'unopim_installed_');

    @unlink($this->marker);

    config(['installer.installed_marker' => $this->marker]);
});

afterEach(function () {
    if (file_exists($this->marker)) {
        @unlink($this->marker);
    }
});

function invokeMarkInstalled(): void
{
    $command = app(Installer::class);

    $method = new ReflectionMethod($command, 'markInstalled');
    $method->setAccessible(true);
    $method->invoke($command);
}

it('writes the storage/installed marker so the installer is sealed', function () {
    if (file_exists($this->marker)) {
        unlink($this->marker);
    }

    invokeMarkInstalled();

    expect(file_exists($this->marker))->toBeTrue();
});

it('dispatches unopim.installed when sealing a fresh install', function () {
    if (file_exists($this->marker)) {
        unlink($this->marker);
    }

    Event::fake();

    invokeMarkInstalled();

    Event::assertDispatched('unopim.installed');
});

it('is idempotent and does not re-dispatch when already sealed', function () {
    file_put_contents($this->marker, 'already installed');

    Event::fake();

    invokeMarkInstalled();

    Event::assertNotDispatched('unopim.installed');
    expect(file_get_contents($this->marker))->toBe('already installed');
});
