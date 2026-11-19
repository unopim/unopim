<?php

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Helpers\DatabaseManager;

function withRawTables(array $definitions, Closure $callback): mixed
{
    $grammar = DB::getQueryGrammar();

    foreach ($definitions as $table => $columns) {
        DB::statement('CREATE TABLE '.$grammar->wrap($table).' ('.$columns.')');
    }

    try {
        return $callback();
    } finally {
        foreach (array_reverse(array_keys($definitions)) as $table) {
            DB::statement('DROP TABLE '.$grammar->wrap($table));
        }
    }
}

function withOtherInstallTables(Closure $callback): mixed
{
    return withRawTables([
        'otherpfx_migrations' => 'id integer, migration varchar(255), batch integer',
        'otherpfx_admins'     => 'id integer',
        'otherpfx_attributes' => 'id integer',
    ], $callback);
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

it('does not take a table that only ends in migrations for another install', function () {
    $prefixes = withRawTables([
        'otherpfx_migrations' => 'id integer, migration varchar(255), batch integer',
        'datapfx_migrations'  => 'id integer, payload varchar(255)',
        'datapfx_admins'      => 'id integer',
    ], fn () => resolve(DatabaseManager::class)->otherInstallPrefixes());

    expect($prefixes)->not->toContain('otherpfx_')
        ->not->toContain('datapfx_');
});

it('gives a table to the longest install prefix it starts with', function () {
    $owningPrefix = new ReflectionMethod(DatabaseManager::class, 'owningPrefix');

    $manager = resolve(DatabaseManager::class);

    expect($owningPrefix->invoke($manager, '3X_attributes', ['', '3', '3X_']))->toBe('3X_')
        ->and($owningPrefix->invoke($manager, '3attributes', ['', '3', '3X_']))->toBe('3')
        ->and($owningPrefix->invoke($manager, 'attributes', ['', '3', '3X_']))->toBe('');
});

it('matches the table prefix in lower case where the server stores table names in lower case', function () {
    $manager = new class extends DatabaseManager
    {
        protected function ignoresTableNameCase(Connection $connection): bool
        {
            return true;
        }
    };

    $comparable = (new ReflectionMethod($manager, 'comparableName'))->invoke($manager, DB::connection());
    $owningPrefix = new ReflectionMethod($manager, 'owningPrefix');

    expect($owningPrefix->invoke($manager, $comparable('3x_attributes'), [$comparable('3X_'), '']))->toBe($comparable('3X_'));
});

it('keeps the case of table names on PostgreSQL', function () {
    $ignoresCase = (new ReflectionMethod(DatabaseManager::class, 'ignoresTableNameCase'))
        ->invoke(resolve(DatabaseManager::class), DB::connection());

    expect($ignoresCase)->toBeFalse();
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'PostgreSQL only');

it('refuses to drop tables that a table outside the prefix references', function () {
    $ensureNoOutsideForeignKeys = new ReflectionMethod(DatabaseManager::class, 'ensureNoOutsideForeignKeys');
    $manager = resolve(DatabaseManager::class);
    $grammar = DB::getQueryGrammar();

    withRawTables([
        'otherpfx_parents'  => 'id integer primary key',
        'otherpfx_children' => 'id integer, parent_id integer, foreign key ('.$grammar->wrap('parent_id').') references '.$grammar->wrap('otherpfx_parents').' ('.$grammar->wrap('id').')',
    ], function () use ($ensureNoOutsideForeignKeys, $manager) {
        expect(fn () => $ensureNoOutsideForeignKeys->invoke($manager, DB::connection(), ['otherpfx_parents']))
            ->toThrow(Exception::class, 'otherpfx_children -> otherpfx_parents');

        $ensureNoOutsideForeignKeys->invoke($manager, DB::connection(), ['otherpfx_parents', 'otherpfx_children']);
    });
})->skip(fn () => DB::getDriverName() === 'pgsql', 'PostgreSQL refuses the drop itself because it runs without CASCADE');
