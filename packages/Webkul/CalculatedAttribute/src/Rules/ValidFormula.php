<?php

namespace Webkul\CalculatedAttribute\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\CalculatedAttribute\Exceptions\InvalidFormulaException;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\FormulaValidator;

/**
 * Validates the formula submitted for a calculated attribute.
 *
 * The rule is what turns a silent failure into a visible one: without it a
 * malformed formula would save happily and only show up as an empty export
 * column weeks later.
 */
class ValidFormula implements ValidationRule
{
    public function __construct(
        protected string $code,
        protected ?FormulaValidator $validator = null,
        protected ?AttributeRegistry $registry = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $registry = $this->registry ?? app(AttributeRegistry::class);

        /*
         * The registry is memoised per process; an attribute saved earlier in
         * the same request would otherwise be invisible to the cycle check.
         */
        $registry->flush();

        $validator = $this->validator ?? app(FormulaValidator::class);

        try {
            $validator->validate($this->code, is_string($value) ? $value : null);
        } catch (InvalidFormulaException $exception) {
            $fail($exception->getMessage());
        }
    }
}
