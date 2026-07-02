<?php

namespace Webkul\Measurement\Normalizer;

use Webkul\Attribute\Services\AttributeService;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer as BaseProductAttributeValuesNormalizer;

class ProductAttributeValuesNormalizer extends BaseProductAttributeValuesNormalizer
{
    public function __construct(
        AttributeService $attributeService,
        protected MeasurementHelper $measurementHelper
    ) {
        parent::__construct($attributeService);
    }

    /**
     * Normalize attribute data with measurement export columns.
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

            if ($attribute->type === 'measurement' && 'true' == ($options['forExport'] ?? '')) {
                [$amount, $unit] = $this->getMeasurementAmountAndUnit($value);

                $values[$attributeCode] = EscapeFormulaOperators::escapeValue($amount);
                $values["{$attributeCode}(unit)"] = EscapeFormulaOperators::escapeValue(
                    $this->measurementHelper->getUnitLabel($unit, $attribute, $options['locale'] ?? null)
                );

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
     * Extract amount and unit from the stored measurement value.
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
}
