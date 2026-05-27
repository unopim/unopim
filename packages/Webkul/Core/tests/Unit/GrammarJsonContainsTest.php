<?php

use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

describe('Grammar::jsonContains — MySQL', function () {

    it('produces JSON_CONTAINS with JSON_EXTRACT for a single path segment', function () {
        $result = (new MySQLGrammar)->jsonContains('values', ['categories'], '?');

        expect($result)->toBe("JSON_CONTAINS(JSON_EXTRACT(`values`, '$.categories'), ?)");
    });

    it('produces correct path for nested segments', function () {
        $result = (new MySQLGrammar)->jsonContains('values', ['channel_locale_specific', 'en_US', 'name'], '?');

        expect($result)->toBe("JSON_CONTAINS(JSON_EXTRACT(`values`, '$.channel_locale_specific.en_US.name'), ?)");
    });

    it('escapes a table-qualified column with backticks', function () {
        $result = (new MySQLGrammar)->jsonContains('p.values', ['categories'], '?');

        expect($result)->toBe("JSON_CONTAINS(JSON_EXTRACT(`p`.`values`, '$.categories'), ?)");
    });

    it('accepts a literal value instead of a placeholder', function () {
        $result = (new MySQLGrammar)->jsonContains('values', ['tags'], '"electronics"');

        expect($result)->toContain('JSON_CONTAINS');
        expect($result)->toContain('"electronics"');
    });
});

describe('Grammar::jsonContains — PostgreSQL', function () {

    it('produces jsonb containment operator for a single path segment', function () {
        $result = (new PostgresGrammar)->jsonContains('values', ['categories'], '?');

        expect($result)->toBe('("values"->\'categories\')::jsonb @> ?::jsonb');
    });

    it('produces chained arrow operators for nested segments', function () {
        $result = (new PostgresGrammar)->jsonContains('values', ['channel_locale_specific', 'en_US', 'name'], '?');

        expect($result)->toBe('("values"->\'channel_locale_specific\'->\'en_US\'->\'name\')::jsonb @> ?::jsonb');
    });

    it('double-quotes a table-qualified column', function () {
        $result = (new PostgresGrammar)->jsonContains('p.values', ['categories'], '?');

        expect($result)->toBe('("p"."values"->\'categories\')::jsonb @> ?::jsonb');
    });

    it('uses @> containment operator not LIKE or IN', function () {
        $result = (new PostgresGrammar)->jsonContains('values', ['tags'], '?');

        expect($result)->toContain('@>');
        expect($result)->not->toContain('LIKE');
        expect($result)->not->toContain(' IN ');
    });
});
