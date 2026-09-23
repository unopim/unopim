<?php

use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

/**
 * Open a PDO connection for an engine under test, or skip when the server,
 * the driver or the credentials are not available in this environment.
 */
function grammarEngineConnection(string $driver, string $dsnEnv, string $userEnv, string $passEnv): PDO
{
    $dsn = env($dsnEnv);

    if (! $dsn) {
        test()->markTestSkipped("{$dsnEnv} is not set.");
    }

    if (! in_array($driver, PDO::getAvailableDrivers(), true)) {
        test()->markTestSkipped("The {$driver} PDO driver is not installed.");
    }

    try {
        return new PDO($dsn, env($userEnv), env($passEnv), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        test()->markTestSkipped("Cannot reach {$dsnEnv}: {$e->getMessage()}");
    }
}

/**
 * A JSON document whose attribute code starts with a digit — the shape that
 * MySQL rejects with error 3143 unless the path member is double-quoted.
 */
function grammarEngineDocument(): string
{
    return json_encode(['common' => ['165attrnum_mu4c2eqrdb0c096e' => 'hit']]);
}

it('extracts a digit-leading attribute path on MySQL', function () {
    $pdo = grammarEngineConnection('mysql', 'GRAMMAR_TEST_MYSQL_DSN', 'GRAMMAR_TEST_MYSQL_USERNAME', 'GRAMMAR_TEST_MYSQL_PASSWORD');

    $expression = (new MySQLGrammar)->jsonExtract('t.vals', 'common', '165attrnum_mu4c2eqrdb0c096e');
    $document = grammarEngineDocument();

    $row = $pdo->query("select {$expression} as x from (select CAST('{$document}' AS JSON) as vals) t")->fetch(PDO::FETCH_ASSOC);

    expect($row['x'])->toBe('hit');
});

it('extracts a digit-leading attribute path on MariaDB', function () {
    $pdo = grammarEngineConnection('mysql', 'GRAMMAR_TEST_MARIADB_DSN', 'GRAMMAR_TEST_MARIADB_USERNAME', 'GRAMMAR_TEST_MARIADB_PASSWORD');

    $expression = (new MySQLGrammar)->jsonExtract('t.vals', 'common', '165attrnum_mu4c2eqrdb0c096e');
    $document = grammarEngineDocument();

    // MariaDB has no native JSON type, so the document stays a string literal.
    $row = $pdo->query("select {$expression} as x from (select '{$document}' as vals) t")->fetch(PDO::FETCH_ASSOC);

    expect($row['x'])->toBe('hit');
});

it('extracts a digit-leading attribute path on PostgreSQL', function () {
    $pdo = grammarEngineConnection('pgsql', 'GRAMMAR_TEST_PGSQL_DSN', 'GRAMMAR_TEST_PGSQL_USERNAME', 'GRAMMAR_TEST_PGSQL_PASSWORD');

    $expression = (new PostgresGrammar)->jsonExtract('t.vals', 'common', '165attrnum_mu4c2eqrdb0c096e');
    $document = grammarEngineDocument();

    $row = $pdo->query("select {$expression} as x from (select '{$document}'::jsonb as vals) t")->fetch(PDO::FETCH_ASSOC);

    expect($row['x'])->toBe('hit');
});
