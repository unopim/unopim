<?php

namespace Webkul\Product\Validator;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Product\Models\Product;
use Webkul\Product\Type\AbstractType;

/**
 * Checks a product against every required attribute of its family, not only the
 * ones the request carried.
 *
 * The edit page loads attribute groups on demand, so a save legitimately submits
 * a subset of the family; without this the required attributes of a group the
 * editor never scrolled to would go unchecked.
 */
class RequiredAttributesValidator
{
    /**
     * Get the required attributes this product is still missing in the given
     * scope, keyed by the form field name that holds them.
     *
     * @return array<string, array{code: string, name: string, group_id: int}>
     */
    public function missing(Product $product, array $submitted, string $channelCode, string $localeCode): array
    {
        if (! $product->attribute_family_id) {
            return [];
        }

        $values = $this->merge($product->values ?? [], $submitted);

        $missing = [];

        foreach ($this->requiredAttributes($product->attribute_family_id) as $attribute) {
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

    /**
     * Get the family's required attributes, each tagged with the group that
     * renders it — the first one in display order, as the edit page shows it.
     *
     * @return Collection<int, Attribute>
     */
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

    /**
     * Overlay the submitted values on the stored ones, the same way the product
     * update merges them, so an attribute saved earlier still counts as filled.
     */
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
