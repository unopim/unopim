<?php

namespace Webkul\Attribute\Services;

use Webkul\Attribute\Contracts\DerivedValueResolver;
use Webkul\Attribute\Support\ProductContext;

/**
 * The default {@see DerivedValueResolver}: nothing is derived.
 *
 * Every payload is returned exactly as it arrived, so a core installation
 * behaves as though the extension point did not exist.
 */
class NullDerivedValueResolver implements DerivedValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function hasDerivedValues(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ProductContext $product, ?string $channel, ?string $locale): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function mergeInto(array $values, ProductContext $product, ?string $channel, ?string $locale): array
    {
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function mergeAllScopes(array $values, ProductContext $product, array $channelsAndLocales): array
    {
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function mergeForProduct(object $product, ?string $channel, ?string $locale): array
    {
        return (array) ($product->values ?? []);
    }
}
