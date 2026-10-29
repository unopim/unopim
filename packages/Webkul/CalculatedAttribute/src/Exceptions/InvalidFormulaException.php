<?php

namespace Webkul\CalculatedAttribute\Exceptions;

use RuntimeException;

/**
 * Raised when a formula cannot be accepted: bad syntax, an unknown variable, a
 * function outside the whitelist, or a circular dependency.
 *
 * The message is written to be shown to an admin as-is, so the named static
 * constructors carry the wording rather than the call sites.
 */
class InvalidFormulaException extends RuntimeException
{
    /**
     * The attribute codes forming a dependency cycle, in order, or an empty
     * array when this exception is not about circularity.
     *
     * @var array<int, string>
     */
    protected array $cycle = [];

    public static function syntax(string $detail): self
    {
        return new self(trans('calculated_attribute::app.formula.errors.syntax', ['detail' => $detail]));
    }

    public static function unknownVariable(string $name): self
    {
        return new self(trans('calculated_attribute::app.formula.errors.unknown-variable', ['name' => $name]));
    }

    public static function disallowedFunction(string $name): self
    {
        return new self(trans('calculated_attribute::app.formula.errors.disallowed-function', ['name' => $name]));
    }

    public static function reservedVariable(string $name): self
    {
        return new self(trans('calculated_attribute::app.formula.errors.reserved-variable', ['name' => $name]));
    }

    public static function tooLong(int $max): self
    {
        return new self(trans('calculated_attribute::app.formula.errors.too-long', ['max' => $max]));
    }

    public static function blank(): self
    {
        return new self(trans('calculated_attribute::app.formula.errors.empty'));
    }

    /**
     * @param  array<int, string>  $cycle  the cycle, already closed (first code repeated last)
     */
    public static function circular(array $cycle): self
    {
        $exception = new self(trans('calculated_attribute::app.formula.errors.circular', [
            'cycle' => implode(' -> ', $cycle),
        ]));

        $exception->cycle = $cycle;

        return $exception;
    }

    /**
     * @return array<int, string>
     */
    public function cycle(): array
    {
        return $this->cycle;
    }
}
