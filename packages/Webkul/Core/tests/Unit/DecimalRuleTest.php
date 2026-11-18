<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Core\Rules\Decimal;

function passesDecimalRule(mixed $value): bool
{
    return Validator::make(['value' => $value], ['value' => [new Decimal]])->passes();
}

it('accepts unsigned and negative decimals', function (string $value) {
    expect(passesDecimalRule($value))->toBeTrue();
})->with(['20', '20.5', '0.1234', '.5', '-20', '-20.5', '-0.5', '-.5']);

it('rejects malformed decimals', function (string $value) {
    expect(passesDecimalRule($value))->toBeFalse();
})->with(['-', '-.', '--5', '5-', '+5', '1.23456', 'abc', '1.2.3']);
