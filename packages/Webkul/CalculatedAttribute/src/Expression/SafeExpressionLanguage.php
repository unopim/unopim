<?php

namespace Webkul\CalculatedAttribute\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Symfony's ExpressionLanguage with its built-in function table removed.
 *
 * The stock component registers `constant()`, `enum()`, `min()` and `max()` in
 * registerFunctions(). `constant()` and `enum()` read arbitrary PHP constants,
 * which is a disclosure primitive we do not want reachable from an admin text
 * field, so registerFunctions() is overridden here to register *nothing* and the
 * whitelist in {@see FormulaFunctions} becomes the complete function table
 * (it re-declares safe min/max itself).
 *
 * The second half of the sandbox is not here but at the call site: the grammar
 * has property-access and method-call nodes, so a formula could reach into any
 * object placed in the evaluation context. The resolver therefore binds only
 * scalars, nulls and arrays, never an Eloquent model.
 */
class SafeExpressionLanguage extends ExpressionLanguage
{
    public function __construct(FormulaFunctions $functions)
    {
        parent::__construct(null, [$functions]);
    }

    /**
     * Deliberately empty: the parent implementation is what registers the
     * built-ins we are dropping. Do not call parent::registerFunctions().
     */
    protected function registerFunctions(): void
    {
        // No built-ins. Every callable name comes from FormulaFunctions.
    }

    /**
     * The names currently registered, used to produce a helpful message when a
     * formula calls something that does not exist.
     *
     * @return array<int, string>
     */
    public function registeredFunctionNames(): array
    {
        return array_keys($this->functions);
    }
}
