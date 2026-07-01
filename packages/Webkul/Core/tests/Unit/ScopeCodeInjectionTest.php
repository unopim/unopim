<?php

use Webkul\Core\Helpers\Database\Grammars\MySQLGrammar;
use Webkul\Core\Helpers\Database\Grammars\PostgresGrammar;

it('accepts valid locale and channel codes', function (string $code) {
    expect(core()->isValidScopeCode($code))->toBeTrue();
})->with(['en_US', 'fr_FR', 'zh_CN', 'default', 'ecommerce', 'a-b_1']);

it('rejects codes that could break out of a SQL literal', function (string $code) {
    expect(core()->isValidScopeCode($code))->toBeFalse();
})->with([
    ["en_US'"],
    ["en_US.name',(select version()),'"],
    ['en_US / sleep(15)'],
    ['en US'],
    [''],
]);

it('rejects non-string scope codes', function () {
    expect(core()->isValidScopeCode(null))->toBeFalse()
        ->and(core()->isValidScopeCode(['en_US']))->toBeFalse()
        ->and(core()->isValidScopeCode(123))->toBeFalse();
});

it('falls back to the default locale when the request locale is malicious', function () {
    request()->merge(['locale' => "en_US',(select version()),'"]);

    expect(core()->getRequestedLocaleCode())->toBe(app()->getLocale());
});

it('returns null for a malicious locale when fallback is disabled', function () {
    request()->merge(['locale' => "en_US'"]);

    expect(core()->getRequestedLocaleCode('locale', false))->toBeNull();
});

it('escapes single quotes in MySQL json path segments', function () {
    $sql = (new MySQLGrammar)->jsonExtract('additional_data', 'locale_specific', "en_US'", 'name');

    expect($sql)->not->toContain("en_US'.name")
        ->and($sql)->toContain("en_US''");
});

it('escapes single quotes in Postgres json path segments', function () {
    $sql = (new PostgresGrammar)->jsonExtract('additional_data', 'locale_specific', "en_US'", 'name');

    expect($sql)->toContain("en_US''");
});
