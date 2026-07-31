<?php

namespace Webkul\ProductPassport\Services;

use WeakMap;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Enums\PassportFieldSource;

class PassportAttributeRequirements
{
    /**
     * @var WeakMap<Product, array<string, array<int, list<array{key: string, label: string, title: string, variant: string}>>>>
     */
    private WeakMap $resolved;

    public function __construct(
        private readonly PassportFeature $feature,
        private readonly PassportReadinessService $readiness,
    ) {
        $this->resolved = new WeakMap;
    }

    /**
     * @return array<int, list<array{key: string, label: string, title: string, variant: string}>>
     */
    public function for(Product $product, Channel $channel, Locale $locale): array
    {
        $scopeKey = $channel->id.':'.$locale->id;
        $productRequirements = $this->resolved[$product] ?? [];

        if (array_key_exists($scopeKey, $productRequirements)) {
            return $productRequirements[$scopeKey];
        }

        if (! $this->feature->enabledFor($channel)) {
            $productRequirements[$scopeKey] = [];
            $this->resolved[$product] = $productRequirements;

            return [];
        }

        $assessment = $this->readiness->assess($product, $channel, $locale);

        if ($assessment->template === null) {
            $productRequirements[$scopeKey] = [];
            $this->resolved[$product] = $productRequirements;

            return [];
        }

        $missingAttributeIds = $assessment->missingFields
            ->where('source_type', PassportFieldSource::Attribute)
            ->pluck('attribute_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $productRequirements[$scopeKey] = $assessment->template->fields
            ->where('is_required', true)
            ->where('source_type', PassportFieldSource::Attribute)
            ->whereNotNull('attribute_id')
            ->unique('attribute_id')
            ->mapWithKeys(function ($field) use ($missingAttributeIds): array {
                $missing = in_array((int) $field->attribute_id, $missingAttributeIds, true);

                return [
                    (int) $field->attribute_id => [[
                        'key'     => 'dpp',
                        'label'   => trans('passport::app.catalog.products.edit.passport.required-badge'),
                        'title'   => trans($missing
                            ? 'passport::app.catalog.products.edit.passport.publish-blocked'
                            : 'passport::app.catalog.products.edit.passport.required-badge'),
                        'variant' => $missing ? 'danger' : 'info',
                    ]],
                ];
            })
            ->all();

        $this->resolved[$product] = $productRequirements;

        return $productRequirements[$scopeKey];
    }
}
