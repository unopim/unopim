<?php

namespace Webkul\CalculatedAttribute\Services;

use Webkul\CalculatedAttribute\Exceptions\InvalidFormulaException;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Expression\SafeExpressionLanguage;
use Webkul\CalculatedAttribute\Support\ProductContext;

/**
 * Evaluates calculated attributes for a product in a given channel/locale.
 *
 * Every consumer — product form, export, API — goes through this one class, so
 * the compute-on-read decision recorded in the design document can be revisited
 * by adding a cache here without touching any caller.
 */
class CalculatedValueResolver
{
    /**
     * Depth limit for calculated attributes referencing other calculated
     * attributes. FormulaValidator keeps the stored graph acyclic, so this only
     * fires on a graph edited directly in the database, where it degrades to an
     * empty value instead of exhausting the stack.
     */
    protected const MAX_DEPTH = 16;

    public function __construct(
        protected SafeExpressionLanguage $expressionLanguage,
        protected FormulaCompiler $compiler,
        protected FormulaValidator $validator,
        protected AttributeRegistry $registry,
    ) {}

    /**
     * Compute every calculated attribute for one product in one scope.
     *
     * @return array<string, mixed> code => computed value
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
     * Merge computed values into a `values` array under each attribute's own
     * scope, leaving stored values untouched.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function mergeInto(array $values, ProductContext $product, ?string $channel, ?string $locale): array
    {
        foreach ($this->resolve($product, $channel, $locale) as $code => $value) {
            $this->registry->placeValue($values, $code, $value, $channel, $locale);
        }

        return $values;
    }

    /**
     * Whether anything needs computing at all. Callers on hot paths check this
     * before assembling a ProductContext.
     */
    public function hasCalculatedAttributes(): bool
    {
        return $this->registry->calculatedCodes() !== [];
    }

    /**
     * The product's stored values with calculated attributes merged in, for the
     * product form.
     *
     * The stored values are the base, so nothing a user actually entered is
     * disturbed; only the calculated codes are added.
     *
     * @return array<string, mixed>
     */
    public function mergeForProduct(object $product, ?string $channel, ?string $locale): array
    {
        $values = (array) ($product->values ?? []);

        if (! $this->hasCalculatedAttributes()) {
            return $values;
        }

        return $this->mergeInto($values, ProductContext::fromProduct($product), $channel, $locale);
    }

    /**
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
            $compiled = $this->compiler->compile($formula, $this->validator->allowedNames($code));
        } catch (InvalidFormulaException) {
            /*
             * A stored formula that no longer compiles (a referenced attribute
             * was deleted) must not break the product page or abort an export
             * mid-run; it renders as empty, and the attribute form surfaces the
             * real error the next time it is saved.
             */
            return $resolved[$code] = null;
        }

        $context = $this->buildContext($compiled->dependencies, $product, $channel, $locale, $resolved, $depth);

        try {
            $value = $this->expressionLanguage->evaluate($compiled->parsed, $context);
        } catch (\Throwable) {
            return $resolved[$code] = null;
        }

        return $resolved[$code] = $this->normalise($value);
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
