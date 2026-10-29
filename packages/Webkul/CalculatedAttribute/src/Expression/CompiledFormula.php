<?php

namespace Webkul\CalculatedAttribute\Expression;

use Symfony\Component\ExpressionLanguage\ParsedExpression;

/**
 * A formula that has passed validation, together with everything the resolver
 * needs to evaluate it without parsing again.
 *
 * Holding the ParsedExpression (rather than the source string) matters: handing
 * it back to ExpressionLanguage::evaluate() skips the parser entirely, which is
 * what keeps compute-on-read cheap across thousands of export rows.
 */
final class CompiledFormula
{
    /**
     * @param  array<int, string>  $dependencies  variable names the formula reads
     */
    public function __construct(
        public readonly string $expression,
        public readonly ParsedExpression $parsed,
        public readonly array $dependencies,
    ) {}
}
