<?php

namespace Webkul\ProductPassport\Services;

use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Contracts\PassportTemplate as PassportTemplateContract;
use Webkul\ProductPassport\Models\PassportTemplateProxy;

/**
 * Resolves the passport template a product publishes through.
 *
 * A family belongs to at most one template, so the answer is a single lookup with
 * no precedence rules. Templates and their rows are memoized per family for the
 * lifetime of the instance, which keeps a bulk publish of one family to a single
 * query set without holding request state on the container.
 */
class PassportTemplateResolver
{
    /** @var array<int, PassportTemplateContract|null> */
    private array $byFamily = [];

    public function forProduct(Product $product): ?PassportTemplateContract
    {
        return $this->forFamily((int) $product->attribute_family_id);
    }

    public function forFamily(int $familyId): ?PassportTemplateContract
    {
        if (array_key_exists($familyId, $this->byFamily)) {
            return $this->byFamily[$familyId];
        }

        return $this->byFamily[$familyId] = PassportTemplateProxy::modelClass()::query()
            ->where('is_enabled', true)
            ->whereHas('families', fn ($query) => $query->where('attribute_families.id', $familyId))
            ->with([
                'translations',
                'sections.translations',
                'fields' => fn ($query) => $query->orderBy('position'),
                'fields.translations',
                'fields.section.translations',
                'fields.attribute.translations',
                'fields.attribute.options.translations',
            ])
            ->first();
    }
}
