<?php

use Webkul\Installer\Console\Commands\Installer;

/**
 * A driver the wizard never offers is a driver nobody can install onto, so the
 * prompt has to stay in step with the shipped connections.
 */
it('offers every server-based driver the application ships a connection for', function () {
    expect(Installer::DATABASE_DRIVERS)->toContain('mysql', 'mariadb', 'pgsql');
});

it('offers no driver without a matching connection in the database config', function () {
    $configured = collect(config('database.connections'))->pluck('driver')->unique();

    expect($configured)->toContain(...Installer::DATABASE_DRIVERS);
});
