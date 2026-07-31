<?php

use Webkul\Installer\Http\Controllers\InstallerController;

/**
 * `PHP_BINARY` is empty on some web SAPIs and points at php-fpm on others.
 * Either value spawns a child process that cannot run artisan, which the web
 * installer reports only as "Permission denied" and exit code 127.
 */
it('resolves a runnable php executable for spawned processes', function () {
    $controller = new class extends InstallerController
    {
        public function __construct() {}

        public function phpBinary(): string
        {
            return $this->resolvePhpBinary();
        }
    };

    $binary = $controller->phpBinary();

    expect($binary)->not->toBe('')
        ->and(basename($binary))->not->toContain('fpm');

    if (str_contains($binary, DIRECTORY_SEPARATOR)) {
        expect(is_executable($binary))->toBeTrue();
    }
});
