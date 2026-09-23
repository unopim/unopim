<?php

use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

describe('Grammar::jsonExtract — MySQL path member quoting', function () {

    it('leaves a plain identifier segment unquoted', function () {
        $result = (new MySQLGrammar)->jsonExtract('values', 'common', 'url_key');

        expect($result)->toBe("JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.common.url_key'))");
    });

    it('double-quotes a segment starting with a digit', function () {
        $result = (new MySQLGrammar)->jsonExtract('values', 'common', '165attrnum_mu4c2eqrdb0c096e');

        expect($result)->toBe("JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.common.\"165attrnum_mu4c2eqrdb0c096e\"'))");
    });

    it('double-quotes a segment containing a hyphen or space', function () {
        expect((new MySQLGrammar)->jsonExtract('values', 'common', 'attr-code'))
            ->toBe("JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.common.\"attr-code\"'))");

        expect((new MySQLGrammar)->jsonExtract('values', 'locale_specific', 'en US', 'name'))
            ->toBe("JSON_UNQUOTE(JSON_EXTRACT(`values`, '$.locale_specific.\"en US\".name'))");
    });

    it('escapes a double quote inside a quoted segment', function () {
        $result = (new MySQLGrammar)->jsonExtract('values', 'common', '1a"b');

        expect($result)->toContain('\\"b');
        expect($result)->not->toContain('"1a"b"');
    });

    it('keeps the SQL string literal escaping for single quotes', function () {
        $result = (new MySQLGrammar)->jsonExtract('values', 'common', "o'brien");

        expect($result)->toContain("o''brien");
    });

    it('quotes digit-leading segments in jsonContains paths too', function () {
        $result = (new MySQLGrammar)->jsonContains('p.values', ['common', '165attrnum'], '?');

        expect($result)->toBe("JSON_CONTAINS(JSON_EXTRACT(`p`.`values`, '$.common.\"165attrnum\"'), ?)");
    });
});

describe('Grammar::jsonExtract — PostgreSQL path members', function () {

    it('quotes every segment as a string key regardless of shape', function () {
        $result = (new PostgresGrammar)->jsonExtract('values', 'common', '165attrnum_mu4c2eqrdb0c096e');

        expect($result)->toBe('"values"->\'common\'->>\'165attrnum_mu4c2eqrdb0c096e\'');
    });
});
