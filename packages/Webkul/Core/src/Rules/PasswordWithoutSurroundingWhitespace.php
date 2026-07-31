<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a password that opens or closes with whitespace.
 *
 * Such a password is nearly always an accident — a stray space from a paste or
 * an autofill — and it locks the owner out as soon as they type the password by
 * hand. Whitespace inside a password is left alone: passphrases legitimately
 * contain spaces.
 */
class PasswordWithoutSurroundingWhitespace implements ValidationRule
{
    /**
     * The validator skips non-implicit rules whenever a string value trims to
     * empty, so a password of nothing but spaces would never reach this rule.
     */
    public bool $implicit = true;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (trim($value) === '') {
            $fail('core::validation.password-whitespace-only')->translate();

            return;
        }

        if (trim($value) !== $value) {
            $fail('core::validation.password-surrounding-whitespace')->translate();
        }
    }
}
