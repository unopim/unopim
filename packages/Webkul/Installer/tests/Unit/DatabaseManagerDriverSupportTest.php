<?php

use Webkul\Installer\Helpers\DatabaseManager;

/**
 * `migrate:fresh` cannot create the schema itself, so a driver excluded from
 * the CREATE DATABASE gate fails the install with a missing-database error.
 *
 * Each case is driven through the name validation: reaching the invalid-name
 * exception proves the driver passed the gate, while a silent return proves it
 * was skipped. Neither path needs a reachable server.
 */
function attemptDatabaseCreation(string $driver): Closure
{
    config([
        'database.default'                              => 'upgrade_driver_probe',
        'database.connections.upgrade_driver_probe'     => ['driver' => $driver, 'database' => 'not a valid name'],
    ]);

    return fn () => app(DatabaseManager::class)->createDatabaseIfNotExists();
}

it('creates the database for a mysql connection', function () {
    expect(attemptDatabaseCreation('mysql'))->toThrow(Exception::class, 'is invalid');
});

it('creates the database for a mariadb connection', function () {
    expect(attemptDatabaseCreation('mariadb'))->toThrow(Exception::class, 'is invalid');
});

it('creates the database for a pgsql connection', function () {
    expect(attemptDatabaseCreation('pgsql'))->toThrow(Exception::class, 'is invalid');
});

it('skips drivers that need no explicit create statement', function () {
    attemptDatabaseCreation('sqlite')();
})->throwsNoExceptions();
