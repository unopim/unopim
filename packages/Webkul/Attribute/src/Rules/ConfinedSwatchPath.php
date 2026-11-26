<?php

namespace Webkul\Attribute\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An image swatch is a path the repository later reads and deletes from the media disk, so a path-like
 * value is accepted only inside the directory the swatch upload stores to; a colour has no separator.
 */
class ConfinedSwatchPath implements ValidationRule
{
    public const DIRECTORY = 'attribute_option';

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('#[/\\\\]|\.\.#', $value)) {
            return;
        }

        if (! str_starts_with($value, self::DIRECTORY.'/') || str_contains($value, '..') || str_contains($value, '\\')) {
            $fail('validation.regex')->translate(['attribute' => $attribute]);
        }
    }
}
