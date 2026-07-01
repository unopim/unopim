<?php

use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

it('escapes a reserved-word column in MySQL json extraction', function () {
    $sql = (new MySQLGrammar)->jsonExtract('values', 'common', 'url_key');

    // `values` is a MySQL reserved word; it must be back-quoted or the query
    // fails with SQLSTATE 1064 during product imports.
    expect($sql)->toContain('`values`')
        ->and($sql)->not->toContain('JSON_EXTRACT(values,');
});

it('escapes a table-qualified column in MySQL json extraction', function () {
    $sql = (new MySQLGrammar)->jsonExtract('products.values', 'common', 'url_key');

    expect($sql)->toContain('`products`.`values`');
});

it('escapes a reserved-word column in Postgres json extraction', function () {
    $sql = (new PostgresGrammar)->jsonExtract('values', 'common', 'url_key');

    expect($sql)->toContain('"values"')
        ->and($sql)->not->toContain('values->');
});
