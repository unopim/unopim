<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Helpers\DatabaseManager;

function withOtherInstallTables(Closure $callback): mixed
{
    $grammar = DB::getQueryGrammar();
    $tables = ['otherpfx_migrations', 'otherpfx_attributes'];

    foreach ($tables as $table) {
        DB::statement('CREATE TABLE '.$grammar->wrap($table).' (id integer)');
    }

    try {
        return $callback();
    } finally {
        foreach ($tables as $table) {
            DB::statement('DROP TABLE '.$grammar->wrap($table));
        }
    }
}

function tableNames(array $qualifiedNames): array
{
    return array_map(fn (string $name): string => str_contains($name, '.') ? substr($name, strrpos($name, '.') + 1) : $name, $qualifiedNames);
}

it('finds the prefixes of other installs from their migrations tables', function () {
    $prefixes = withOtherInstallTables(fn () => resolve(DatabaseManager::class)->otherInstallPrefixes());

    expect($prefixes)->toContain('otherpfx_')
        ->not->toContain(DB::getTablePrefix());
});

it('keeps the tables of other installs out of the tables a reinstall drops', function () {
    $tables = tableNames(withOtherInstallTables(fn () => resolve(DatabaseManager::class)->currentPrefixTables()));

    expect($tables)->toContain(DB::getTablePrefix().'migrations')
        ->toContain(DB::getTablePrefix().'admins')
        ->not->toContain('otherpfx_migrations')
        ->not->toContain('otherpfx_attributes');
});

it('gives a table to the longest install prefix it starts with', function () {
    $owningPrefix = new ReflectionMethod(DatabaseManager::class, 'owningPrefix');

    $manager = resolve(DatabaseManager::class);

    expect($owningPrefix->invoke($manager, '3X_attributes', ['', '3', '3X_']))->toBe('3X_')
        ->and($owningPrefix->invoke($manager, '3attributes', ['', '3', '3X_']))->toBe('3')
        ->and($owningPrefix->invoke($manager, 'attributes', ['', '3', '3X_']))->toBe('');
});
