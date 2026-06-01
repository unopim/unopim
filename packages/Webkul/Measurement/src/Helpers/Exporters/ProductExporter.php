<?php

namespace Webkul\Measurement\Helpers\Exporters;

use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;

class ProductExporter extends CoreExporter
{
    /**
     * Prepare product attribute values for export.
     *
     * @return array
     */
    protected function setAttributesValues(array $values, mixed $filePath)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) ($filters['with_media'] ?? false);

        foreach ($this->attributes as $attribute) {
            $code = $attribute->code;

            if (in_array($code, ['sku', 'status'])) {
                continue;
            }

            $rawValue = $values[$code] ?? null;

            if ($attribute->type === 'measurement') {

                $measurementData = is_array($rawValue) ? $rawValue : [];

                $amount = null;
                $unit = null;

                if (isset($measurementData['<all_channels>']['<all_locales>'])) {
                    $localeData = $measurementData['<all_channels>']['<all_locales>'];
                    $amount = $localeData['amount'] ?? null;
                    $unit = $localeData['unit'] ?? null;
                } elseif (isset($measurementData['unit']) && isset($measurementData['amount'])) {
                    $amount = $measurementData['amount'];
                    $unit = $measurementData['unit'];
                }

                $attributeValues[$code] = EscapeFormulaOperators::escapeValue($amount);
                $attributeValues["{$code}(unit)"] = EscapeFormulaOperators::escapeValue(
                    app(MeasurementHelper::class)->getUnitLabel($unit, $attribute)
                );

                continue;
            }

            if ($attribute->type === AttributeTypes::PRICE_ATTRIBUTE_TYPE) {
                $priceData = is_array($rawValue) ? $rawValue : [];

                foreach ($this->currencies as $currency) {
                    $attributeValues["{$code} ({$currency})"] = $priceData[$currency] ?? null;
                }

                continue;
            }

            if (is_array($rawValue)) {
                $rawValue = implode(', ', $rawValue);
            }

            $attributeValues[$code] = EscapeFormulaOperators::escapeValue($rawValue);
        }

        return $attributeValues;
    }
}
