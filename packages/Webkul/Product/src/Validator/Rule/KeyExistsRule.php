<?php

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KeyExistsRule implements ValidationRule
{
    /**
     * create validation rule object
     */
    public function __construct(protected array $keys, protected string $removeFromKey = '') {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $attribute = str_replace($this->removeFromKey, '', $attribute);

        if (! in_array($attribute, $this->keys)) {
            $fail(sprintf('Unexpected key %s', $attribute));
        }
    }
}
