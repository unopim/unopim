<?php

use Illuminate\Support\Facades\URL;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\DemoDataInstaller;
use Webkul\Installer\Helpers\EnvironmentManager;
use Webkul\Installer\Helpers\ServerRequirements;
use Webkul\Installer\Http\Controllers\InstallerController;

beforeEach(function () {

    $this->withoutMiddleware();

    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    $this->marker = storage_path('installed');
    $this->markerExisted = file_exists($this->marker);
    $this->markerContents = $this->markerExisted ? file_get_contents($this->marker) : null;

    if ($this->markerExisted) {
        unlink($this->marker);
    }
});

afterEach(function () {
    if ($this->markerExisted) {
        file_put_contents($this->marker, $this->markerContents);
    } elseif (file_exists($this->marker)) {
        unlink($this->marker);
    }
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

describe('InstallerController::resolveComposerBinary', function () {
    it('probes the bundled bin/composer/composer.phar', function () {
        $controller = app(InstallerController::class);

        $method = new ReflectionMethod($controller, 'composerProbePaths');
        $paths = $method->invoke($controller);

        expect($paths)->toContain(base_path('bin/composer/composer.phar'))
            ->and($paths)->toContain(base_path('composer.phar'));
    });

    it('returns a php + phar argument prefix when only the bundled phar exists', function () {
        $controller = new class(app(ServerRequirements::class), app(EnvironmentManager::class), app(DatabaseManager::class)) extends InstallerController
        {
            protected function composerProbePaths(): array
            {
                return [base_path('bin/composer/composer.phar')];
            }

            public function exposedResolveComposerBinary(): array
            {
                return $this->resolveComposerBinary();
            }
        };

        expect($controller->exposedResolveComposerBinary())
            ->toBe([PHP_BINARY, base_path('bin/composer/composer.phar')]);
    });
});
