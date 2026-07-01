<?php

use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

it('does not double-quote pre-quoted string ids in MySQL FIELD ordering', function () {
    $sql = (new MySQLGrammar)->orderByField('type', ["'image'", "'gallery'"], 'text');

    expect($sql)->toBe("FIELD(type, 'image','gallery')");
});

it('keeps integer ids intact in MySQL FIELD ordering', function () {
    $sql = (new MySQLGrammar)->orderByField('products.id', [3, 1, 2]);

    expect($sql)->toBe('FIELD(products.id, 3,1,2)');
});

it('does not double-quote pre-quoted string values in Postgres ordering', function () {
    $sql = (new PostgresGrammar)->orderByField('type', ["'image'", "'gallery'"], 'text');

    expect($sql)->toBe("array_position(ARRAY['image','gallery']::text[], type)");
});

it('escapes json path segments in MySQL jsonContains', function () {
    $sql = (new MySQLGrammar)->jsonContains('values', ['common', "x'y"], '1');

    expect($sql)->toContain("x''y")
        ->and($sql)->toContain('`values`');
});
