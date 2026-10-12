<?php

namespace Webkul\Product\Validator;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Product\Models\Product;
use Webkul\Product\Services\VariantStructurePlanner;
use Webkul\Product\Type\AbstractType;

/** Checks a product against every required attribute of its family. */
class RequiredAttributesValidator
{
    public function __construct(
        protected VariantStructurePlanner $variantStructurePlanner
    ) {}

    /** @return array<string, array{code: string, name: string, group_id: int}> */
    public function missing(Product $product, array $submitted, string $channelCode, string $localeCode): array
    {
        if (! $product->attribute_family_id) {
            return [];
        }

        $stored = $product->parent_id ? $product->resolvedValues() : ($product->values ?? []);

        $values = $this->merge($stored, $submitted);

        $missing = [];

        foreach ($this->requiredAttributes($product->attribute_family_id) as $attribute) {
            if (! $this->variantStructurePlanner->ownsAttribute($product, $attribute->code)) {
                continue;
            }

            $value = $attribute->getValueFromProductValues($values, $channelCode, $localeCode);

            if (! $this->isEmpty($value)) {
                continue;
            }

            $field = AbstractType::PRODUCT_VALUES_KEY.$attribute->getAttributeInputFieldName($channelCode, $localeCode);

            $missing[$field] = [
                'code'     => $attribute->code,
                'name'     => $attribute->name ?: $attribute->code,
                'group_id' => (int) $attribute->getAttribute('attribute_group_id'),
            ];
        }

        return $missing;
    }

    /** @return Collection<int, Attribute> */
    protected function requiredAttributes(int $familyId): Collection
    {
        $groupByAttribute = DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->join('attributes', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->where('attribute_family_group_mappings.attribute_family_id', $familyId)
            ->where('attributes.is_required', 1)
            ->orderBy('attribute_family_group_mappings.position')
            ->select([
                'attribute_group_mappings.attribute_id',
                'attribute_family_group_mappings.attribute_group_id',
            ])
            ->get()
            ->reduce(function (array $carry, $row): array {
                $carry[$row->attribute_id] ??= (int) $row->attribute_group_id;

                return $carry;
            }, []);

        if ($groupByAttribute === []) {
            return collect();
        }

        return (AttributeProxy::modelClass())::query()
            ->whereIn('id', array_keys($groupByAttribute))
            ->get()
            ->each(fn (Attribute $attribute) => $attribute->setAttribute(
                'attribute_group_id',
                $groupByAttribute[$attribute->id] ?? 0
            ));
    }

    /** Overlay the submitted values on the stored ones. */
    protected function merge(array $stored, array $submitted): array
    {
        foreach ($submitted as $section => $sectionValues) {
            if (! is_array($sectionValues)) {
                continue;
            }

            $stored[$section] = array_replace_recursive($stored[$section] ?? [], $sectionValues);
        }

        return $stored;
    }

    protected function isEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        return $value === null || $value === '';
    }
}
