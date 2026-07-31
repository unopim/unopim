<?php

use Illuminate\Support\Facades\File;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;

/**
 * Admin forms, account settings, and password reset all reject a password
 * with surrounding whitespace; the installer must too, or it mints the one
 * admin account whose password the login page's leading-space guard can
 * never reproduce.
 */
beforeEach(function () {
    $this->withoutMiddleware();

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

    app()->instance(EnvironmentManager::class, new class(app(DatabaseManager::class)) extends EnvironmentManager
    {
        public function setEnvConfiguration(array $request): bool
        {
            return true;
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
    File::delete(storage_path('app/installer-state.json'));

    if ($this->markerExisted && ! file_exists($this->marker)) {
        file_put_contents($this->marker, (string) $this->markerContents);
    }
});

it('rejects an admin password that begins or ends with whitespace', function (string $password) {
    $this->postJson('/install/api/prepare', [
        'admin' => [
            'admin'    => 'Admin',
            'email'    => 'admin@example.test',
            'password' => $password,
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['admin.password']);

    expect(file_exists(storage_path('app/installer-state.json')))->toBeFalse();
})->with([
    'leading space'  => [' secret123'],
    'trailing space' => ['secret123 '],
    'spaces only'    => ['        '],
]);

it('accepts an admin password with only interior spaces', function () {
    $this->postJson('/install/api/prepare', [
        'admin' => [
            'admin'    => 'Admin',
            'email'    => 'admin@example.test',
            'password' => 'pass phrase 123',
        ],
    ])->assertOk();

    expect(file_exists(storage_path('app/installer-state.json')))->toBeTrue();
});
