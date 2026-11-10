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
     * The attribute's own code is a legal identifier as far as the parser is
     * concerned — the allowed-name set covers the whole attribute table so the
     * compile cache can be keyed on the formula alone — so the trivial
     * `a = a` self-reference is rejected here instead.
     *
     * @param  string  $code  the attribute being saved; may not yet exist
     *
     * @throws InvalidFormulaException
     */
    public function validate(string $code, ?string $formula): CompiledFormula
    {
        $compiled = $this->compiler->compile($formula);

        if (in_array($code, $compiled->dependencies, true)) {
            throw InvalidFormulaException::circular([$code, $code]);
        }

        $this->assertAcyclic($code, $compiled->dependencies);

        return $compiled;
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
        $explored = [];

        foreach ($dependencies as $dependency) {
            if (! $this->registry->isCalculated($dependency)) {
                continue;
            }

            $path = $this->findPath($dependency, $code, [$dependency], $explored);

            if ($path !== null) {
                throw InvalidFormulaException::circular(array_merge([$code], $path));
            }
        }
    }

    /**
     * Depth-first search from `$from` to `$target` across calculated attributes.
     *
     * An already-stored formula that no longer compiles (its source attribute
     * was renamed or deleted) contributes no edges: failing here would block
     * editing an unrelated attribute.
     *
     * `$explored` is shared by every search made for one save. An attribute
     * whose dependencies have all been searched without reaching `$target`
     * can never reach it, so it is expanded once per save rather than once per
     * route that leads to it. Without that, the cost grows with the number of
     * distinct paths, which is exponential for a ladder of formulas that each
     * read all the previous ones.
     *
     * @param  array<int, string>  $visited  the path walked so far, used both as
     *                                       the cycle report and as the guard
     *                                       against looping on a graph that is
     *                                       already cyclic in the database
     * @param  array<string, true>  $explored  attributes already searched to the end
     * @return array<int, string>|null the path including `$target`, or null
     */
    protected function findPath(string $from, string $target, array $visited, array &$explored): ?array
    {
        if ($from === $target) {
            return $visited;
        }

        if (isset($explored[$from])) {
            return null;
        }

        $explored[$from] = true;

        $formula = $this->registry->formula($from);

        if ($formula === null || trim($formula) === '') {
            return null;
        }

        try {
            $compiled = $this->compiler->compile($formula);
        } catch (InvalidFormulaException) {
            return null;
        }

        foreach ($compiled->dependencies as $dependency) {
            if ($dependency !== $target && ! $this->registry->isCalculated($dependency)) {
                continue;
            }

            if (in_array($dependency, $visited, true)) {
                continue;
            }

            $path = $this->findPath($dependency, $target, array_merge($visited, [$dependency]), $explored);

            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    /**
     * The calculated attributes whose formula actually reads `$code`.
     *
     * Backs the refusal to delete an attribute something else is derived from.
     * Candidates come from a cheap text match and are then confirmed against
     * the parsed dependency list, so a code that only appears inside a string
     * literal does not count.
     *
     * @return array<int, string>
     */
    public function dependents(string $code): array
    {
        $dependents = [];

        foreach ($this->registry->formulasMentioning($code) as $candidate) {
            if ($candidate === $code) {
                continue;
            }

            try {
                $compiled = $this->compiler->compile($this->registry->formula($candidate));
            } catch (InvalidFormulaException) {
                continue;
            }

            if (in_array($code, $compiled->dependencies, true)) {
                $dependents[] = $candidate;
            }
        }

        return $dependents;
    }
}
