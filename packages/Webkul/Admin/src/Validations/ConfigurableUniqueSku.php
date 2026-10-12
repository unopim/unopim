<?php

namespace Webkul\Admin\Validations;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Product\Repositories\ProductRepository;

class ConfigurableUniqueSku implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected array $currentIds = [],
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->isSkuUniqueAcrossVariants()) {
            return;
        }

        $fail('admin::app.catalog.products.index.variant-sku-already-taken')->translate([
            'sku' => (string) $value,
        ]);
    }

    /**
     * Determine if the requested variant skus are unique across other products and within the request itself.
     */
    protected function isSkuUniqueAcrossVariants(): bool
    {
        $requestedSkus = collect(request()->input('variants'))->pluck('sku')->toArray();

        $productRepository = app(ProductRepository::class);

        if (
            $productRepository->whereIn('sku', $requestedSkus)
                ->whereNotIn('id', $this->currentIds)
                ->exists()
        ) {
            return false;
        }

        return count($requestedSkus) === count(array_unique($requestedSkus));
    }
}
