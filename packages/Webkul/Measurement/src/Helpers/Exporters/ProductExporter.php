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
    protected function setAttributesValues(array $values, mixed $filePath, ?string $locale = null)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) ($filters['with_media'] ?? false);

        $formatOutput = ($filters['use_labels'] ?? '0') !== '0'
            || ! empty($filters['date_format'] ?? null);

        foreach ($this->attributeMeta as $meta) {
            $code = $meta['code'];
            $type = $meta['type'];
            $attribute = $meta['attribute'];

            if ($code === 'sku' || $code === 'status') {
                continue;
            }

            $isPrice = $type === AttributeTypes::PRICE_ATTRIBUTE_TYPE;

            if (! $this->isAttributeValueExported($code)) {
                if ($isPrice) {
                    foreach ($this->currencies as $currency) {
                        $attributeValues["{$code} ({$currency})"] = null;
                    }

                    continue;
                }

                if ($type === 'measurement') {
                    $attributeValues[$code] = null;
                    $attributeValues["{$code}(unit)"] = null;

                    continue;
                }

                $attributeValues[$code] = null;

                continue;
            }

            $rawValue = $values[$code] ?? null;

            if ($type === 'measurement') {
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

            if (
                $withMedia
                && ($type === AttributeTypes::FILE_ATTRIBUTE_TYPE
                    || $type === AttributeTypes::IMAGE_ATTRIBUTE_TYPE
                    || $type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE)
            ) {
                $mediaPaths = (array) $rawValue;
                foreach ($mediaPaths as $path) {
                    if (! empty($path)) {
                        $this->copyMedia($path, $filePath->getTemporaryPath().'/'.$path);
                    }
                }

                $attributeValues[$code] = implode(', ', array_filter($mediaPaths));

                continue;
            }

            if ($isPrice) {
                $priceData = is_array($rawValue) ? $rawValue : [];

                foreach ($this->currencies as $currency) {
                    $attributeValues["{$code} ({$currency})"] = $priceData[$currency] ?? null;
                }

                continue;
            }

            if ($formatOutput) {
                $rawValue = $this->applyOutputFormatting($meta['attribute'], $rawValue, $locale);
            }

            if (is_array($rawValue)) {
                $rawValue = implode(', ', $rawValue);
            }

            $attributeValues[$code] = EscapeFormulaOperators::escapeValue($rawValue);
        }

        return $attributeValues;
    }
}
