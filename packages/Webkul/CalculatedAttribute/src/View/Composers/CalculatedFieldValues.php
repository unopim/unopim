<?php

namespace Webkul\CalculatedAttribute\View\Composers;

use Illuminate\View\View;
use Webkul\CalculatedAttribute\Services\CalculatedValueResolver;

/**
 * Puts the computed values in front of the product form's attribute fields.
 *
 * A calculated attribute holds no stored value, so the panel would otherwise
 * render it empty. Composing the view rather than calling a service from Blade
 * keeps the core template free of any reference to this package, and leaves
 * `fieldValues` untouched — the template then falls back to the product's own
 * values — when there is nothing to compute.
 */
class CalculatedFieldValues
{
    public function __construct(
        private readonly CalculatedValueResolver $resolver,
    ) {}

    public function compose(View $view): void
    {
        if (! $this->resolver->hasDerivedValues()) {
            return;
        }

        $data = $view->getData();

        $product = $data['product'] ?? null;

        if (! is_object($product)) {
            return;
        }

        $channel = $data['currentChannel'] ?? core()->getRequestedChannel();
        $locale = $data['currentLocale'] ?? core()->getRequestedLocale();

        $view->with('fieldValues', $this->resolver->mergeForProduct(
            $product,
            $channel?->code,
            $locale?->code
        ));
    }
}
