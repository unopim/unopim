<?php

namespace Webkul\Product\Normalizer;

use Webkul\Attribute\Services\AttributeService;
use Webkul\Product\Type\AbstractType;

/**
 * TODO: create seperate formatters to format according to attribute type
 */
class ProductAttributeValuesNormalizer
{
    /**
     * Constructor for object creation
     */
    public function __construct(
        protected AttributeService $attributeService
    ) {}

    /**
     * Normalize attribute data with options for product
     */
    public function normalizeAttributes(array $attributeValues, array $options = []): array
    {
        $values = [];

        if (empty($options['locale'])) {
            $options['locale'] = core()->getRequestedLocaleCode();
        }

        foreach ($attributeValues as $attributeCode => $value) {
            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute) {
                continue;
            }

            if ($attribute->type == 'price' && 'true' == ($options['forExport'] ?? '')) {
                $value = ! is_array($value) ? [] : $value;

                foreach ($value as $currency => $price) {
                    $values["{$attributeCode} ({$currency})"] = $price;
                }

                continue;
            }

            if ($attribute->type === 'gallery' && ! empty($value) && is_array($value)) {
                $value = implode(', ', $value);
            }

            $values[$attributeCode] = EscapeFormulaOperators::escapeValue($value);
        }

        return $values;
    }

    /**
     * Normalize association values for export
     */
    public function normalizeAssociations(array $associationValues, array $options = []): array
    {
        if (empty($associationValues)) {
            return [];
        }

        $values = [];

        foreach (AbstractType::ASSOCIATION_SECTIONS as $section) {
            if (empty($associationValues[$section])) {
                continue;
            }

            $values[$section] = implode(', ', $associationValues[$section]);
        }

        return $values;
    }
}
