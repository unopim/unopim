<?php

namespace Webkul\CalculatedAttribute\Services;

use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Webkul\CalculatedAttribute\Exceptions\InvalidFormulaException;
use Webkul\CalculatedAttribute\Expression\CompiledFormula;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;

/**
 * Turns formula source into a validated, reusable AST and reports the variable
 * names it reads.
 *
 * Dependencies are taken from the parsed tree rather than from a regex over the
 * source: a regex cannot tell `manufacturer` the variable from `'manufacturer'`
 * the string literal, and getting that wrong means either a false circularity
 * rejection or a missed one.
 */
class FormulaCompiler
{
    /**
     * Upper bound on formula length. The grammar has no loops, so expression
     * length is what bounds worst-case evaluation cost.
     */
    public const MAX_LENGTH = 2000;

    /**
     * Rejected outright: `matches` takes a user-supplied regex, which is a
     * denial-of-service foot-gun (catastrophic backtracking inside an export
     * worker) for a capability this feature never advertised.
     */
    protected const FORBIDDEN_OPERATORS = ['matches'];

    /**
     * Memoised per source + allowed-name-set, so repeated compiles of the same
     * formula within a request or an export worker cost one array lookup.
     *
     * @var array<string, CompiledFormula>
     */
    protected array $cache = [];

    public function __construct(
        protected SafeExpressionLanguage $expressionLanguage,
    ) {}

    /**
     * @param  array<int, string>  $allowedNames  every variable the formula may read
     *
     * @throws InvalidFormulaException
     */
    public function compile(?string $formula, array $allowedNames): CompiledFormula
    {
        $formula = trim((string) $formula);

        if ($formula === '') {
            throw InvalidFormulaException::blank();
        }

        if (mb_strlen($formula) > self::MAX_LENGTH) {
            throw InvalidFormulaException::tooLong(self::MAX_LENGTH);
        }

        $allowedNames = array_values(array_unique($allowedNames));

        $cacheKey = $formula.'|'.implode(',', $allowedNames);

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $parsed = $this->expressionLanguage->parse($formula, $allowedNames);
        } catch (SyntaxError $error) {
            throw $this->translateSyntaxError($error);
        }

        $nodes = $parsed->getNodes();

        $this->assertNoForbiddenOperator($nodes);

        $dependencies = $this->collectNames($nodes);

        foreach ($dependencies as $name) {
            if (str_starts_with($name, FormulaFunctions::INTERNAL_PREFIX)) {
                throw InvalidFormulaException::reservedVariable($name);
            }
        }

        return $this->cache[$cacheKey] = new CompiledFormula($formula, $parsed, $dependencies);
    }

    /**
     * Symfony reports every rejection as a SyntaxError. Pull the two cases an
     * admin can actually act on out of the message so the UI can say "unknown
     * attribute" instead of "syntax error".
     */
    protected function translateSyntaxError(SyntaxError $error): InvalidFormulaException
    {
        $message = $error->getMessage();

        if (preg_match('/Variable "([^"]+)" is not valid/', $message, $matches) === 1) {
            return InvalidFormulaException::unknownVariable($matches[1]);
        }

        if (preg_match('/The function "([^"]+)" does not exist/', $message, $matches) === 1) {
            return InvalidFormulaException::disallowedFunction($matches[1]);
        }

        return InvalidFormulaException::syntax($message);
    }

    /**
     * @throws InvalidFormulaException
     */
    protected function assertNoForbiddenOperator(Node $node): void
    {
        if (
            $node instanceof BinaryNode
            && in_array($node->attributes['operator'] ?? null, self::FORBIDDEN_OPERATORS, true)
        ) {
            throw InvalidFormulaException::disallowedFunction((string) $node->attributes['operator']);
        }

        foreach ($node->nodes as $child) {
            if ($child instanceof Node) {
                $this->assertNoForbiddenOperator($child);
            }
        }
    }

    /**
     * Every variable name the expression reads, deduplicated.
     *
     * @param  array<string, true>  $found
     * @return array<int, string>
     */
    protected function collectNames(Node $node, array &$found = []): array
    {
        if ($node instanceof NameNode) {
            $name = $node->attributes['name'] ?? null;

            if (is_string($name)) {
                $found[$name] = true;
            }
        }

        foreach ($node->nodes as $child) {
            if ($child instanceof Node) {
                $this->collectNames($child, $found);
            }
        }

        return array_keys($found);
    }
}
