<?php

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Rules\Decimal;

dataset('fields with client-side rules', [
    'attribute'      => [Attribute::class],
    'category field' => [CategoryField::class],
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
    ['decimal', Decimal::class],
]);
