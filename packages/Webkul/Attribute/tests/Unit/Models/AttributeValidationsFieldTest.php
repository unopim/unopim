<?php

use Webkul\Attribute\Models\Attribute;

dataset('attribute hostile validation settings', [
    'quote in the regex pattern' => [
        ['validation' => 'regex', 'regex_pattern' => 'x", foo: alert(document.domain), bar: "'],
        '{ regex: "x\\u0022, foo: alert(document.domain), bar: \\u0022" }',
    ],
    'markup in the regex pattern' => [
        ['validation' => 'regex', 'regex_pattern' => "</a><img src=x onerror=alert(1)>'"],
        '{ regex: "\\u003C\\/a\\u003E\\u003Cimg src=x onerror=alert(1)\\u003E\\u0027" }',
    ],
    'expression smuggled in as the validation type' => [
        ['validation' => 'foo: alert(document.domain), bar'],
        '{  }',
    ],
]);

it('keeps a hostile validation setting inside a string literal of the attribute rules', function (array $settings, string $expected): void {
    $field = new Attribute;
    $field->forceFill($settings);

    expect($field->getValidationsField())->toBe($expected);
})->with('attribute hostile validation settings');

it('passes a regex pattern with backslashes through to the attribute rules intact', function (): void {
    $field = new Attribute;
    $field->forceFill(['validation' => 'regex', 'regex_pattern' => '^\\d{3}-[A-Z]+$']);

    expect($field->getValidationsField())->toBe('{ regex: "^\\\\d{3}-[A-Z]+$" }');
});

it('still renders the known validation types of the attribute rules', function (string $validation, string $expected): void {
    $field = new Attribute;
    $field->forceFill(['validation' => $validation]);

    expect($field->getValidationsField())->toBe($expected);
})->with([
    ['email', '{ email: true }'],
    ['url', '{ url: true }'],
    ['decimal', '{ decimal: true }'],
    ['number', '{ numeric: true }'],
]);
