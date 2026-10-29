<?php

namespace Webkul\CalculatedAttribute\Services;

use Webkul\CalculatedAttribute\Exceptions\InvalidFormulaException;
use Webkul\CalculatedAttribute\Expression\CompiledFormula;

/**
 * Save-time gate for a formula: syntax, allowed names, allowed functions, and
 * the dependency-graph check that keeps the stored set of formulas acyclic.
 *
 * Because every write goes through here, evaluation can assume a DAG and does
 * not need a cycle detector of its own.
 */
class FormulaValidator
{
    /**
     * Variables always bound during evaluation, regardless of the attribute set.
     */
    public const SYSTEM_VARIABLES = [
        'sku',
        'type',
        'status',
        'family',
        'parent',
        'categories',
        'channel',
        'locale',
    ];

    public function __construct(
        protected FormulaCompiler $compiler,
        protected AttributeRegistry $registry,
    ) {}

    /**
     * Validate the formula a given attribute code is about to be saved with.
     *
     * @param  string  $code  the attribute being saved; may not yet exist
     *
     * @throws InvalidFormulaException
     */
    public function validate(string $code, ?string $formula): CompiledFormula
    {
        $compiled = $this->compiler->compile($formula, $this->allowedNames($code));

        $this->assertAcyclic($code, $compiled->dependencies);

        return $compiled;
    }

    /**
     * Every identifier a formula for `$code` may reference: all other attribute
     * codes plus the system variables.
     *
     * The attribute's own code is excluded, which turns the trivial `a = a`
     * self-reference into an "unknown variable" error at parse time rather than
     * a cycle report. Codes that are not valid expression identifiers (an
     * attribute code may legally start with a digit) are dropped, because the
     * grammar could never name them anyway.
     *
     * @return array<int, string>
     */
    public function allowedNames(string $code): array
    {
        $names = array_filter(
            $this->registry->codes(),
            fn (string $candidate): bool => $candidate !== $code && $this->isIdentifier($candidate)
        );

        return array_values(array_unique(array_merge(array_values($names), self::SYSTEM_VARIABLES)));
    }

    /**
     * Walk the dependency graph looking for a path back to `$code`.
     *
     * Only calculated attributes form edges: a reference to a plain attribute is
     * a leaf, because a stored value cannot depend on anything.
     *
     * @param  array<int, string>  $dependencies
     *
     * @throws InvalidFormulaException
     */
    protected function assertAcyclic(string $code, array $dependencies): void
    {
        foreach ($dependencies as $dependency) {
            if (! $this->registry->isCalculated($dependency)) {
                continue;
            }

            $path = $this->findPath($dependency, $code, [$dependency]);

            if ($path !== null) {
                throw InvalidFormulaException::circular(array_merge([$code], $path));
            }
        }
    }

    /**
     * Depth-first search from `$from` to `$target` across calculated attributes.
     *
     * @param  array<int, string>  $visited  the path walked so far, used both as
     *                                       the cycle report and as the guard
     *                                       against looping on a graph that is
     *                                       already cyclic in the database
     * @return array<int, string>|null the path including `$target`, or null
     */
    protected function findPath(string $from, string $target, array $visited): ?array
    {
        if ($from === $target) {
            return $visited;
        }

        $formula = $this->registry->formula($from);

        if ($formula === null || trim($formula) === '') {
            return null;
        }

        try {
            $compiled = $this->compiler->compile($formula, $this->allowedNames($from));
        } catch (InvalidFormulaException) {
            /*
             * An already-stored formula that no longer compiles (its source
             * attribute was renamed or deleted) contributes no edges. Failing
             * here would block editing an unrelated attribute.
             */
            return null;
        }

        foreach ($compiled->dependencies as $dependency) {
            if ($dependency !== $target && ! $this->registry->isCalculated($dependency)) {
                continue;
            }

            if (in_array($dependency, $visited, true)) {
                continue;
            }

            $path = $this->findPath($dependency, $target, array_merge($visited, [$dependency]));

            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    protected function isIdentifier(string $candidate): bool
    {
        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $candidate) === 1;
    }
}
