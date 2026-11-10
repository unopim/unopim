<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Rules\Decimal;
use Webkul\Product\Models\AssociationTypeField;

dataset('fields with client-side rules', [
    'attribute'              => [Attribute::class],
    'category field'         => [CategoryField::class],
    'association type field' => [AssociationTypeField::class],
]);

/**
 * @param  class-string<Model>  $class
 * @param  array<string, string>  $settings
 */
function clientRulesFor(string $class, array $settings): string
{
    $field = new $class;
    $field->forceFill($settings);

    return $field->getValidationsField();
}

/**
 * @param  class-string<Model>  $class
 * @param  array<string, string>  $settings
 * @return array<int, mixed>
 */
function serverRulesFor(string $class, array $settings): array
{
    $field = new $class;
    $field->forceFill(['type' => 'text', 'code' => 'field_under_test', ...$settings]);

    return $field instanceof Attribute
        ? $field->getValidationRules(withUniqueValidation: false)
        : $field->getValidationsFieldWithOutMedia();
}

it('keeps a quote in the regex pattern inside one string literal', function (string $class): void {
    expect(clientRulesFor($class, ['validation' => 'regex', 'regex_pattern' => 'x", foo: alert(document.domain), bar: "']))
        ->toBe('{ regex: "x\u0022, foo: alert(document.domain), bar: \u0022" }');
})->with('fields with client-side rules');

it('hex-escapes markup and apostrophes in the regex pattern', function (string $class): void {
    expect(clientRulesFor($class, ['validation' => 'regex', 'regex_pattern' => "</a><img src=x onerror=alert(1)>'"]))
        ->toBe('{ regex: "\u003C\/a\u003E\u003Cimg src=x onerror=alert(1)\u003E\u0027" }');
})->with('fields with client-side rules');

it('drops a validation type that is not a plain identifier', function (string $class): void {
    expect(clientRulesFor($class, ['validation' => 'foo: alert(document.domain), bar']))->toBe('{  }');
})->with('fields with client-side rules');

it('passes a regex pattern with backslashes through intact', function (string $class): void {
    expect(clientRulesFor($class, ['validation' => 'regex', 'regex_pattern' => '^\d{3}-[A-Z]+$']))
        ->toBe('{ regex: "^\\\\d{3}-[A-Z]+$" }');
})->with('fields with client-side rules');

it('still renders the known validation types', function (string $class, string $validation, string $expected): void {
    expect(clientRulesFor($class, ['validation' => $validation]))->toBe($expected);
})->with('fields with client-side rules')->with([
    ['email', '{ email: true }'],
    ['url', '{ url: true }'],
    ['decimal', '{ decimal: true }'],
    ['number', '{ numeric: true }'],
    ['numeric', '{ numeric: true }'],
]);

it('does not hand an unknown stored validation type to the server-side validator', function (string $class): void {
    expect(serverRulesFor($class, ['validation' => 'exists:admin_users,email']))
        ->not->toContain('exists:admin_users,email')
        ->toBe(['nullable']);
})->with('fields with client-side rules');

it('maps the known validation types to server-side rules', function (string $class, string $validation, string $expected): void {
    $rules = serverRulesFor($class, ['validation' => $validation]);

    $expected === Decimal::class
        ? expect(end($rules))->toBeInstanceOf(Decimal::class)
        : expect(end($rules))->toBe($expected);
})->with('fields with client-side rules')->with([
    ['email', 'email'],
    ['url', 'url'],
    ['number', 'numeric'],
    ['numeric', 'numeric'],
    ['decimal', Decimal::class],
]);

/**
 * @param  class-string<Model>  $class
 * @param  array<string, string>  $settings
 */
function validatesAgainstStoredSettings(string $class, array $settings, mixed $value): bool
{
    return Validator::make(['field_under_test' => $value], ['field_under_test' => serverRulesFor($class, $settings)])->passes();
}

it('keeps a pipe in the stored regex pattern from adding rules on the server', function (string $class): void {
    $queries = [];

    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $passes = validatesAgainstStoredSettings($class, ['validation' => 'regex', 'regex_pattern' => '.*"|exists:admin_users,email|regex:".*'], 'admin@example.com');

    expect($passes)->toBeFalse()
        ->and(collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'admin_users'))->all())->toBe([]);
})->with('fields with client-side rules');

it('fails the value instead of erroring when the stored regex pattern does not compile', function (string $class): void {
    expect(validatesAgainstStoredSettings($class, ['validation' => 'regex', 'regex_pattern' => 'a"b'], 'a"b'))->toBeFalse();
})->with('fields with client-side rules');

it('matches the submitted value against the stored regex pattern', function (string $class, mixed $value, bool $expected): void {
    expect(validatesAgainstStoredSettings($class, ['validation' => 'regex', 'regex_pattern' => '^\d{3}-[A-Z]+$'], $value))->toBe($expected);
})->with('fields with client-side rules')->with([
    ['123-AB', true],
    ['12-AB', false],
    [['123-AB'], false],
]);

it('rejects non-numeric text on a field with the legacy numeric validation', function (string $class): void {
    expect(validatesAgainstStoredSettings($class, ['validation' => 'numeric'], 'abc'))->toBeFalse()
        ->and(validatesAgainstStoredSettings($class, ['validation' => 'numeric'], '12.5'))->toBeTrue();
})->with('fields with client-side rules');
