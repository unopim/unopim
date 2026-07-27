<?php

namespace Webkul\Publication\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * GS1 mod-10 check-digit validation for GTIN-8/12/13/14. The rightmost digit is
 * the check digit; the remaining digits are weighted 3,1,3,1,… from the right.
 */
class Gs1CheckDigit implements ValidationRule
{
    private const VALID_LENGTHS = [8, 12, 13, 14];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = (string) $value;

        if (! ctype_digit($digits) || ! in_array(strlen($digits), self::VALID_LENGTHS, true)) {
            $fail('passport::app.validation.gtin')->translate();

            return;
        }

        $sum = 0;

        foreach (array_reverse(str_split(substr($digits, 0, -1))) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 3 : 1);
        }

        if ((10 - $sum % 10) % 10 !== (int) substr($digits, -1)) {
            $fail('passport::app.validation.gtin')->translate();
        }
    }
}
