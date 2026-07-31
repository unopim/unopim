<?php

namespace Webkul\Measurement\Helpers\Exporters;

use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;

class ProductExporter extends CoreExporter
{
    /**
     * Prepare product attribute values for export.
     *
     * Measurement attributes are withheld from the parent so it emits an empty
     * placeholder column in its usual position, then filled in here as the
     * amount plus a companion "<code>(unit)" column holding the unit label.
     */
    protected function setAttributesValues(array $values, mixed $filePath, ?string $locale = null): array
    {
        $measurementMeta = array_filter(
            $this->attributeMeta,
            fn (array $meta): bool => ($meta['type'] ?? null) === 'measurement'
        );

        if ($measurementMeta === []) {
            return parent::setAttributesValues($values, $filePath, $locale);
        }

        $attributeValues = parent::setAttributesValues(
            array_diff_key($values, array_fill_keys(array_column($measurementMeta, 'code'), true)),
            $filePath,
            $locale
        );

        $helper = resolve(MeasurementHelper::class);

        foreach ($measurementMeta as $meta) {
            $code = $meta['code'];
            $attribute = $meta['attribute'];

            if (! $this->isAttributeValueExported($code)) {
                $attributeValues[$code] = null;
                $attributeValues["{$code}(unit)"] = null;

                continue;
            }

            [$amount, $unit] = $this->extractMeasurement($values[$code] ?? null);

            $attributeValues[$code] = EscapeFormulaOperators::escapeValue($amount);

            $attributeValues["{$code}(unit)"] = EscapeFormulaOperators::escapeValue(
                $helper->getUnitLabel($unit, $attribute, $locale)
            );
        }

        return $attributeValues;
    }

    /**
     * Resolve the amount and unit from a stored measurement value.
     */
    protected function extractMeasurement(mixed $value): array
    {
        $data = is_array($value) ? $value : [];

        if (isset($data['<all_channels>']['<all_locales>'])) {
            $data = $data['<all_channels>']['<all_locales>'];
        }

        return [
            $data['amount'] ?? null,
            $data['unit'] ?? null,
        ];
    }
}
