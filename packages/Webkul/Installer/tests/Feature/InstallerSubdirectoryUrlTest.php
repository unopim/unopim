<?php

use Illuminate\Support\Facades\URL;
use Webkul\Installer\Helpers\DatabaseManager;

/**
 * CoreServiceProvider pins URL generation to APP_URL, which is still the
 * .env.example default while the installer runs — so any server-generated
 * URL in the wizard is unusable on a sub-directory deployment
 * (e.g. http://host/unopim/public/install behind Apache). Every endpoint the
 * wizard's JavaScript calls must therefore be rebased against the browser's
 * own location before use, and the APP_URL prefill must keep the sub-path.
 */
beforeEach(function () {
    $this->withoutMiddleware();

    config(['app.url' => 'http://localhost:8000']);
    URL::forceRootUrl('http://localhost:8000');

    app()->instance(DatabaseManager::class, new class extends DatabaseManager
    {
        public function isInstalled(): bool
        {
            return false;
        }

        public function isMarkedInstalled(): bool
        {
            return false;
        }
    });

    $this->marker = storage_path('installed');
    $this->markerExisted = file_exists($this->marker);
    $this->markerContents = $this->markerExisted ? file_get_contents($this->marker) : null;

    if ($this->markerExisted) {
        unlink($this->marker);
    }
});

afterEach(function () {
    if ($this->markerExisted && ! file_exists($this->marker)) {
        file_put_contents($this->marker, (string) $this->markerContents);
    }
});

it('rebases every installer api endpoint on the browser location', function () {
    $html = $this->get('/install')->assertOk()->getContent();

    foreach (['env_file_setup', 'prepare', 'process', 'seed_sample_data'] as $name) {
        $path = route("installer.{$name}", [], false);

        expect($html)->toContain('resolveInstallerUrl("'.$path.'")');
    }
});

it('switches wizard locale relative to the current path, not the web root', function () {
    $html = $this->get('/install')->assertOk()->getContent();

    expect($html)->not->toContain("window.location.href='/install?locale='")
        ->and($html)->toContain("window.location.pathname + '?locale='");
});
