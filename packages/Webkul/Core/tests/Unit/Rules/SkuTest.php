<?php

use Webkul\Core\Rules\Sku;

function skuRuleFails(mixed $value): bool
{
    return validator(
        ['sku' => $value],
        ['sku' => ['required', new Sku]]
    )->fails();
}

it('rejects a blank sku', function (string $value) {
    expect(skuRuleFails($value))->toBeTrue();
})->with([
    'empty string'   => [''],
    'only spaces'    => ['   '],
]);

it('is skipped for an absent value on a nullable field, matching every real caller', function () {
    expect(
        validator([], ['sku' => ['nullable', new Sku]])->fails()
    )->toBeFalse();
});

it('rejects a sku longer than 255 characters', function () {
    expect(skuRuleFails(str_repeat('A', 256)))->toBeTrue();
});

it('accepts a sku exactly 255 characters long', function () {
    expect(skuRuleFails(str_repeat('A', 255)))->toBeFalse();
});

it('rejects a sku with leading or trailing spaces', function (string $value) {
    expect(skuRuleFails($value))->toBeTrue();
})->with([
    'leading space'  => [' SKU123'],
    'trailing space' => ['SKU123 '],
    'both sides'     => [' SKU123 '],
]);

it('rejects a sku containing a comma or semicolon', function (string $value) {
    expect(skuRuleFails($value))->toBeTrue();
})->with([
    'comma'     => ['SKU,123'],
    'semicolon' => ['SKU;123'],
]);

it('accepts a valid sku', function (string $value) {
    expect(skuRuleFails($value))->toBeFalse();
})->with([
    'plain alphanumeric'  => ['SKU123'],
    'with hyphen'         => ['SKU-123'],
    'with underscore'     => ['SKU_123'],
    'with percent sign'   => ['SKU%123'],
    'leading percent'     => ['%SKU123'],
    'leading hyphen'      => ['-SKU123'],
    'consecutive dash'    => ['SKU--123'],
]);
