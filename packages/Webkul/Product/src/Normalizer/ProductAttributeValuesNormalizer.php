<?php

namespace Webkul\Product\Normalizer;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Services\AttributeService;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;
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

        $measurementHelper = null;

        if (empty($options['locale'])) {
            $options['locale'] = core()->getRequestedLocaleCode();
        }

        foreach ($attributeValues as $attributeCode => $value) {
            $attribute = $this->attributeService->findAttributeByCode($attributeCode);

            if (! $attribute instanceof Attribute) {
                continue;
            }

            if ($attribute->type === 'measurement' && 'true' == ($options['forExport'] ?? '')) {
                [$amount, $unit] = $this->getMeasurementAmountAndUnit($value);

                $measurementHelper ??= resolve(MeasurementHelper::class);

                $values[$attributeCode] = EscapeFormulaOperators::escapeValue($amount);
                $values["{$attributeCode}(unit)"] = EscapeFormulaOperators::escapeValue(
                    $measurementHelper->getUnitLabel($unit, $attribute, $options['locale'] ?? null)
                );

                continue;
            }

            if ($attribute->type == 'price' && 'true' == ($options['forExport'] ?? '')) {
                $value = is_array($value) ? $value : [];

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
     * Extract the amount and unit from a stored measurement value, unwrapping the
     * <all_channels>/<all_locales> envelope and accepting a JSON string.
     */
    protected function getMeasurementAmountAndUnit(mixed $value): array
    {
        if (is_string($value)) {
            $decodedValue = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decodedValue;
            }
        }

        if (! is_array($value)) {
            return [null, null];
        }

        if (isset($value['<all_channels>']['<all_locales>'])) {
            $value = $value['<all_channels>']['<all_locales>'];
        }

        return [
            $value['amount'] ?? $value['value'] ?? null,
            $value['unit'] ?? null,
        ];
    }

    /**
     * Normalize association values for export
     */
    public function normalizeAssociations(array $associationValues, array $options = []): array
    {
        if ($associationValues === []) {
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
