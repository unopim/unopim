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
     * Operators rejected outright.
     *
     * `matches` takes a user-supplied regex, which is a denial-of-service
     * foot-gun (catastrophic backtracking inside an export worker) for a
     * capability this feature never advertised. `..` materialises its whole
     * range as an array when evaluated, so `1..100000000` is the same risk by a
     * different route.
     */
    protected const FORBIDDEN_OPERATORS = ['matches', '..'];

    /**
     * Parsed formulas, keyed by source alone.
     *
     * Which names are allowed is a property of the attribute table, not of any
     * one formula, so it is not part of the key; `$snapshot` records the
     * version of that table the entries were parsed against, and the cache is
     * dropped when it moves.
     *
     * @var array<string, CompiledFormula>
     */
    protected array $cache = [];

    protected ?int $snapshot = null;

    public function __construct(
        protected SafeExpressionLanguage $expressionLanguage,
        protected AttributeRegistry $registry,
    ) {}

    /**
     * @throws InvalidFormulaException
     */
    public function compile(?string $formula): CompiledFormula
    {
        $formula = trim((string) $formula);

        if ($formula === '') {
            throw InvalidFormulaException::blank();
        }

        if (mb_strlen($formula) > self::MAX_LENGTH) {
            throw InvalidFormulaException::tooLong(self::MAX_LENGTH);
        }

        $this->syncWithRegistry();

        if (isset($this->cache[$formula])) {
            return $this->cache[$formula];
        }

        try {
            $parsed = $this->expressionLanguage->parse($formula, $this->registry->allowedNames());
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

        return $this->cache[$formula] = new CompiledFormula($formula, $parsed, $dependencies);
    }

    /**
     * Drop everything parsed against an attribute table that has since changed.
     */
    protected function syncWithRegistry(): void
    {
        $version = $this->registry->version();

        if ($this->snapshot === $version) {
            return;
        }

        $this->cache = [];
        $this->snapshot = $version;
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
            throw InvalidFormulaException::disallowedOperator((string) $node->attributes['operator']);
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
