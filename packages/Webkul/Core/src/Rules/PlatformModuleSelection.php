<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Core\Platform\ModuleGate;

class PlatformModuleSelection implements ValidationRule
{
    public function __construct(protected ModuleGate $gate) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_array($value)) {
            $fail(trans('core::validation.platform-modules.empty'));

            return;
        }

        foreach ($this->gate->selectionErrors(ModuleGate::parseModules($value)) as $error) {
            $fail($error);
        }
    }
}
