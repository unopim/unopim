<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;

/**
 * An empty string reaching an integer column is never valid on any engine, so
 * the normalisation cannot be gated on a driver allowlist that omits MariaDB.
 */
function normalizeFor(string $driver, array $data): array
{
    config()->set("database.connections.fake_{$driver}", ['driver' => $driver, 'database' => 'unopim_fake']);

    $original = DB::getDefaultConnection();

    DB::setDefaultConnection("fake_{$driver}");

    try {
        return (new ReflectionMethod(JobInstancesRepository::class, 'normalizeData'))
            ->invoke(app(JobInstancesRepository::class), $data);
    } finally {
        DB::setDefaultConnection($original);
    }
}

it('normalizes an empty allowed_errors on every driver', function (string $driver) {
    expect(normalizeFor($driver, ['allowed_errors' => ''])['allowed_errors'])->toBe(0);
})->with(['mysql', 'mariadb', 'pgsql']);

it('nulls empty path columns only where the engine rejects an empty string', function () {
    expect(normalizeFor('pgsql', ['file_path' => '', 'images_directory_path' => '']))
        ->toMatchArray(['file_path' => null, 'images_directory_path' => null]);
});

it('leaves populated values untouched', function () {
    expect(normalizeFor('mariadb', ['allowed_errors' => 5, 'file_path' => 'imports/a.csv']))
        ->toMatchArray(['allowed_errors' => 5, 'file_path' => 'imports/a.csv']);
});
