<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Publication\Rules\Gs1CheckDigit;

function validateGtin(string $value): bool
{
    return Validator::make(['gtin' => $value], ['gtin' => [new Gs1CheckDigit]])->passes();
}

it('accepts a valid GTIN-13', fn () => expect(validateGtin('4006381333931'))->toBeTrue());

it('accepts a valid GTIN-8', fn () => expect(validateGtin('40170725'))->toBeTrue());

it('accepts a valid GTIN-14', fn () => expect(validateGtin('10614141000415'))->toBeTrue());

it('rejects a bad check digit', fn () => expect(validateGtin('4006381333932'))->toBeFalse());

it('rejects a non-numeric value', fn () => expect(validateGtin('40063813339AB'))->toBeFalse());

it('rejects a wrong-length value', fn () => expect(validateGtin('12345'))->toBeFalse());
