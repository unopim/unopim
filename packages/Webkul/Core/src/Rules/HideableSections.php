<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Core\Platform\ModuleGate;

class HideableSections implements ValidationRule
{
    public function __construct(protected ModuleGate $gate) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && ! is_string($value) && ! is_array($value)) {
            $fail('core::validation.hideable-sections')->translate();

            return;
        }

        if (array_diff(ModuleGate::parseSections($value), $this->gate->hideableSections()) !== []) {
            $fail('core::validation.hideable-sections')->translate();
        }
    }
}
