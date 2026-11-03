<?php

namespace Webkul\CalculatedAttribute\Services;

use Webkul\Attribute\Contracts\DerivedValueResolver;
use Webkul\Attribute\Support\ProductContext;
use Webkul\CalculatedAttribute\Exceptions\InvalidFormulaException;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;

/**
 * Evaluates calculated attributes for a product in a given channel/locale.
 *
 * Every consumer — product form, export, API — reaches this through the core
 * {@see DerivedValueResolver} contract, so the compute-on-read decision
 * recorded in the design document can be revisited by adding a cache here
 * without touching any caller.
 */
class CalculatedValueResolver implements DerivedValueResolver
{
    /**
     * Depth limit for calculated attributes referencing other calculated
     * attributes. FormulaValidator keeps the stored graph acyclic, so this only
     * fires on a graph edited directly in the database, where it degrades to an
     * empty value instead of exhausting the stack.
     */
    protected const MAX_DEPTH = 16;

    /**
     * Formulas already reported as broken in this process.
     *
     * A failing formula fails for every product in an export, so reporting
     * every occurrence would bury the one useful line under a hundred thousand
     * copies. One report per formula is enough to act on.
     *
     * @var array<string, true>
     */
    protected array $reported = [];

    public function __construct(
        protected SafeExpressionLanguage $expressionLanguage,
        protected FormulaCompiler $compiler,
        protected AttributeRegistry $registry,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function resolve(ProductContext $product, ?string $channel, ?string $locale): array
    {
        $codes = $this->registry->calculatedCodes();

        if ($codes === []) {
            return [];
        }

        $resolved = [];

        foreach ($codes as $code) {
            $resolved[$code] = $this->evaluate($code, $product, $channel, $locale, $resolved, 0);
        }

        return $resolved;
    }

    /**
     * Compute a single calculated attribute. Returns null when the attribute is
     * not calculated or its formula cannot be evaluated.
     */
    public function resolveOne(string $code, ProductContext $product, ?string $channel, ?string $locale): mixed
    {
        if (! $this->registry->isCalculated($code)) {
            return null;
        }

        $resolved = [];

        return $this->evaluate($code, $product, $channel, $locale, $resolved, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeInto(array $values, ProductContext $product, ?string $channel, ?string $locale): array
    {
        foreach ($this->resolve($product, $channel, $locale) as $code => $value) {
            $this->registry->placeValue($values, $code, $value, $channel, $locale);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDerivedValues(): bool
    {
        return $this->registry->calculatedCodes() !== [];
    }

    /**
     * {@inheritdoc}
     *
     * The stored values are the base, so nothing a user actually entered is
     * disturbed; only the calculated codes are added.
     */
    public function mergeForProduct(object $product, ?string $channel, ?string $locale): array
    {
        $values = (array) ($product->values ?? []);

        if (! $this->hasDerivedValues()) {
            return $values;
        }

        return $this->mergeInto($values, ProductContext::fromProduct($product), $channel, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeAllScopes(array $values, ProductContext $product, array $channelsAndLocales): array
    {
        if (! $this->hasDerivedValues()) {
            return $values;
        }

        foreach ($channelsAndLocales as $channel => $locales) {
            foreach ($locales === [] ? [null] : $locales as $locale) {
                $values = $this->mergeInto($values, $product, (string) $channel, $locale);
            }
        }

        return $values;
    }

    /**
     * Compute one attribute, recursing into the calculated attributes it reads.
     *
     * A stored formula that no longer compiles (a referenced attribute was
     * deleted) must not break the product page or abort an export mid-run, so
     * it renders as empty — but silently shipping blank export columns is how
     * such a formula stays broken for weeks, which is why the failure is
     * reported before the empty value is returned.
     *
     * @param  array<string, mixed>  $resolved  memo of values already computed
     *                                          for this product and scope
     */
    protected function evaluate(
        string $code,
        ProductContext $product,
        ?string $channel,
        ?string $locale,
        array &$resolved,
        int $depth,
    ): mixed {
        if (array_key_exists($code, $resolved)) {
            return $resolved[$code];
        }

        if ($depth >= self::MAX_DEPTH) {
            return null;
        }

        $formula = $this->registry->formula($code);

        if ($formula === null || trim($formula) === '') {
            return null;
        }

        try {
            $compiled = $this->compiler->compile($formula);
        } catch (InvalidFormulaException $exception) {
            $this->report($code, $formula, $exception);

            return $resolved[$code] = null;
        }

        $context = $this->buildContext($compiled->dependencies, $product, $channel, $locale, $resolved, $depth);

        try {
            $value = $this->expressionLanguage->evaluate($compiled->parsed, $context);
        } catch (\Throwable $throwable) {
            $this->report($code, $formula, $throwable);

            return $resolved[$code] = null;
        }

        return $resolved[$code] = $this->normalise($value);
    }

    /**
     * Hand a broken formula to the application's error handler, once per
     * formula per process.
     */
    protected function report(string $code, string $formula, \Throwable $throwable): void
    {
        $key = $code."\0".$formula;

        if (isset($this->reported[$key])) {
            return;
        }

        $this->reported[$key] = true;

        report(new \RuntimeException(
            sprintf('Calculated attribute "%s" could not be evaluated: %s', $code, $throwable->getMessage()),
            0,
            $throwable
        ));
    }

    /**
     * Bind exactly the names the formula reads, plus the internal keys the
     * category functions need.
     *
     * Only the referenced names are bound rather than every attribute: it keeps
     * the per-row context small, and it means an attribute added later cannot
     * change the behaviour of an existing formula.
     *
     * @param  array<int, string>  $dependencies
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    protected function buildContext(
        array $dependencies,
        ProductContext $product,
        ?string $channel,
        ?string $locale,
        array &$resolved,
        int $depth,
    ): array {
        $context = [
            FormulaFunctions::CATEGORIES_KEY => $product->categories,
            FormulaFunctions::LOCALE_KEY     => $locale,
        ];

        foreach ($dependencies as $name) {
            $context[$name] = match ($name) {
                'sku'        => $product->sku,
                'type'       => $product->type,
                'status'     => $product->status,
                'family'     => $product->family,
                'parent'     => $product->parent,
                'categories' => $product->categories,
                'channel'    => $channel,
                'locale'     => $locale,
                default      => $this->registry->isCalculated($name)
                    ? $this->evaluate($name, $product, $channel, $locale, $resolved, $depth + 1)
                    : $this->registry->valueFrom($product->values, $name, $channel, $locale),
            };
        }

        return $context;
    }

    /**
     * Keep only value shapes the rest of the application already handles.
     *
     * An expression can return an object (a Carbon instance via a date helper,
     * say); letting one escape would put it into an export cell or a JSON
     * response as something unprintable.
     */
    protected function normalise(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            return FormulaFunctions::toString($value);
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }
}
