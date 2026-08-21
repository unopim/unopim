<?php

use Webkul\Core\Helpers\Database\GrammarQueryManager;
use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

/**
 * Laravel reports MariaDB under its own driver string, so a manager that only
 * knows `mysql` takes down every query that routes through the grammar.
 */
it('resolves the MySQL grammar for the mysql driver', function () {
    expect(GrammarQueryManager::getGrammar('mysql'))->toBeInstanceOf(MySQLGrammar::class);
});

it('resolves the MySQL grammar for the mariadb driver', function () {
    expect(GrammarQueryManager::getGrammar('mariadb'))->toBeInstanceOf(MySQLGrammar::class);
});

it('resolves the Postgres grammar for the pgsql driver', function () {
    expect(GrammarQueryManager::getGrammar('pgsql'))->toBeInstanceOf(PostgresGrammar::class);
});

it('caches a grammar per driver rather than rebuilding it', function () {
    expect(GrammarQueryManager::getGrammar('mariadb'))->toBe(GrammarQueryManager::getGrammar('mariadb'));
});

it('refuses a driver it has no grammar for', function () {
    expect(fn () => GrammarQueryManager::getGrammar('sqlsrv'))->toThrow(RuntimeException::class);
});
