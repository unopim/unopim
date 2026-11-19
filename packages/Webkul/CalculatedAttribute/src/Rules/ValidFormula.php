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
 * column weeks later. It runs for the admin form and for both REST endpoints,
 * which collect it through the core attribute rule event.
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
     *
     * The registry snapshot is dropped first: it is memoised for the request,
     * and an attribute saved earlier in the same one would otherwise be
     * invisible to the cycle check.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $registry = $this->registry ?? app(AttributeRegistry::class);

        $registry->flush();

        $validator = $this->validator ?? app(FormulaValidator::class);

        try {
            $validator->validate($this->code, is_string($value) ? $value : null);
        } catch (InvalidFormulaException $exception) {
            $fail($exception->getMessage());
        }
    }
}
