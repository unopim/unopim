<?php

use Illuminate\Support\Facades\URL;
use Webkul\Installer\Helpers\DemoDataInstaller;

beforeEach(function () {
    // CSRF + CanInstall + auth middleware would otherwise reject these
    // requests with 302/419/404. We test the controller logic directly;
    // those middlewares have their own coverage.
    $this->withoutMiddleware();

    // The shared .env's APP_URL is a path-prefixed dev URL
    // (http://host/issue-docker/unopim/public). That prefix leaks into
    // the test request URI and the route matcher 404s on it. Force a
    // clean APP_URL just for the test kernel.
    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');
});

describe('InstallerController::seedSampleData (issue #794)', function () {
    it('returns success: true when the demo data installer reports success', function () {
        app()->instance(DemoDataInstaller::class, new class extends DemoDataInstaller
        {
            public function seed(?Closure $reporter = null, bool $force = false): array
            {
                return ['success' => true];
            }
        });

        $this->postJson('/install/api/seed-sample-data')
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns 500 and forwards the seeder error message on failure', function () {
        app()->instance(DemoDataInstaller::class, new class extends DemoDataInstaller
        {
            public function seed(?Closure $reporter = null, bool $force = false): array
            {
                return ['success' => false, 'error' => 'demo_extras.json missing'];
            }
        });

        $this->postJson('/install/api/seed-sample-data')
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error'   => 'demo_extras.json missing',
            ]);
    });
});

describe('InstallerController::envFileSetup DB_PREFIX validation (issue #794)', function () {
    it('rejects a prefix containing spaces with the same message the CLI surfaces', function () {
        $this->postJson('/install/api/env-file-setup', [
            'db_prefix' => 'a a',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'The database prefix can only contain letters, numbers, and underscores.');
    });

    it('rejects a prefix longer than 4 characters', function () {
        $this->postJson('/install/api/env-file-setup', [
            'db_prefix' => 'toolong',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'The database prefix should not exceed 4 characters.');
    });

    it('rejects a prefix that contains non-alphanumeric/underscore characters', function () {
        $this->postJson('/install/api/env-file-setup', [
            'db_prefix' => 'bad!',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'The database prefix can only contain letters, numbers, and underscores.');
    });
});
