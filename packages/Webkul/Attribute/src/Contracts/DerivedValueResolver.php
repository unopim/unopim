<?php

namespace Webkul\Attribute\Contracts;

use Webkul\Attribute\Services\NullDerivedValueResolver;
use Webkul\Attribute\Support\ProductContext;

/**
 * Supplies attribute values that are derived at read time instead of stored.
 *
 * Core has no such attribute type of its own; the default binding is
 * {@see NullDerivedValueResolver}, which answers
 * "nothing to derive" and leaves every payload untouched. A package that adds
 * derived values rebinds this contract, which is what lets the product form,
 * the exporter and the admin API pick them up without importing that package.
 */
interface DerivedValueResolver
{
    /**
     * Whether anything needs deriving at all.
     *
     * Callers on hot paths check this before assembling a {@see ProductContext},
     * so an implementation must answer it cheaply.
     */
    public function hasDerivedValues(): bool;

    /**
     * Derive every value for one product in one scope.
     *
     * @return array<string, mixed> attribute code => derived value
     */
    public function resolve(ProductContext $product, ?string $channel, ?string $locale): array;

    /**
     * Merge derived values into a `values` array under each attribute's own
     * scope, leaving stored values untouched.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function mergeInto(array $values, ProductContext $product, ?string $channel, ?string $locale): array;

    /**
     * Merge derived values for every channel/locale combination at once.
     *
     * The admin API returns the whole `values` structure rather than one scope,
     * so a single (channel, locale) pass would leave most of it unpopulated.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, array<int, string>>  $channelsAndLocales  channel code => locale codes
     * @return array<string, mixed>
     */
    public function mergeAllScopes(array $values, ProductContext $product, array $channelsAndLocales): array;

    /**
     * A product's stored values with derived values merged in, for the product
     * form.
     *
     * @return array<string, mixed>
     */
    public function mergeForProduct(object $product, ?string $channel, ?string $locale): array;
}
