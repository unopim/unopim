<?php

namespace Webkul\Measurement\Helpers\Importers\Product;

use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreImporter;
use Webkul\Measurement\Helpers\MeasurementHelper;

class Importer extends CoreImporter
{
    /**
     * Load all attributes and families to use later
     */
    protected function initAttributes(): void
    {
        parent::initAttributes();

        // Add measurement attribute columns to valid column names
        foreach ($this->attributes as $attribute) {
            if ($attribute->type === 'measurement') {
                $this->addMeasurementAttributesColumns($attribute->code);
            }
        }
    }

    /**
     * Add valid column names for the measurement attribute
     */
    public function addMeasurementAttributesColumns(string $attributeCode): void
    {
        $this->validColumnNames[] = $attributeCode;
        $this->validColumnNames[] = $attributeCode.'(unit)';
        $this->validColumnNames[] = $attributeCode.'_value';
        $this->validColumnNames[] = $attributeCode.'_unit';
    }

    /**
     * Prepare validation rules
     */
    public function getValidationRules(array $rowData): array
    {
        $rules = parent::getValidationRules($rowData);

        $attributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE]);

        $skipAttributes = $rowData['type'] == self::PRODUCT_TYPE_CONFIGURABLE ? (explode(',', $rowData['configurable_attributes']) ?? []) : [];
        $skipAttributes = array_map('trim', $skipAttributes);
        $skipAttributes[] = 'sku';

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->code;

            if (in_array($attributeCode, $skipAttributes)) {
                continue;
            }

            $validations = $attribute->getValidationRules(withUniqueValidation: false);

            if ($attribute->type === 'measurement') {
                $rules[$attributeCode] = $validations;

                if (in_array('required', $validations, true)) {
                    $rules[$attributeCode][] = "required_without:{$attributeCode}_value";
                }

                $rules[$attributeCode.'_value'] = $this->getMeasurementValueValidationRules($validations);
                $rules[$attributeCode.'(unit)'] = $this->getMeasurementUnitValidationRules($attributeCode);
                $rules[$attributeCode.'_unit'] = $this->getMeasurementUnitValidationRules($attributeCode);
            }
        }

        return $rules;
    }

    /**
     * Returns validation rules for measurement value columns.
     */
    protected function getMeasurementValueValidationRules(array $validations): array
    {
        $rules = array_values(array_filter($validations, function ($rule) {
            return $rule !== 'required';
        }));

        if (! in_array('nullable', $rules, true)) {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Returns validation rules for measurement unit columns.
     */
    protected function getMeasurementUnitValidationRules(string $attributeCode): array
    {
        return ['nullable', "required_with:{$attributeCode}_value"];
    }

    /**
     * Save products from current batch
     */
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $familyAttributes = $this->getProductTypeFamilyAttributes($rowData['type'], $rowData[self::ATTRIBUTE_FAMILY_CODE]);
        $imageDirPath = $this->import->images_directory_path;

        foreach ($rowData as $columnName => $value) {
            if (is_null($value)) {
                continue;
            }

            // Skip unit columns as they will be processed with their main attribute
            if (str_ends_with($columnName, '(unit)') || str_ends_with($columnName, '_unit')) {
                continue;
            }

            /**
             * Since Price column is added like this price (USD) the below function formats and returns the actual attributeCode from the columnName
             */
            [$attributeCode, $currencyCode] = $this->getAttributeCodeAndCurrency($columnName);

            if (str_ends_with($attributeCode, '_value')) {
                $attributeCode = substr($attributeCode, 0, -6);
            }

            $attribute = $familyAttributes->where('code', $attributeCode)->first();

            if (! $attribute) {
                continue;
            }

            if ($attribute->type === 'gallery') {
                $value = explode(',', $value);
            }

            // Handle measurement attributes with unit column
            if ($attribute->type === 'measurement') {
                $unitColumnName = $attributeCode.'(unit)';
                $unitUnderscoreName = $attributeCode.'_unit';

                $unit = $rowData[$unitColumnName]
                    ?? $rowData[$unitUnderscoreName]
                    ?? null;

                $unit = app(MeasurementHelper::class)->resolveUnitCode(
                    $unit,
                    $attribute,
                    $rowData['locale'] ?? null
                );

                // ALWAYS normalize structure
                if (! is_null($value)) {
                    $value = [
                        'value' => (float) $value,
                        'unit'  => $unit ?? null,
                    ];
                }

                $value = $this->fieldProcessor->handleField($attribute, $value, $imageDirPath);

            } else {
                $value = $this->fieldProcessor->handleField($attribute, $value, $imageDirPath);

                if ($attribute->type === 'price') {
                    $value = $this->formatPriceValueWithCurrency($currencyCode, $value, $attribute->getValueFromProductValues($attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null));
                }
            }

            $value = EscapeFormulaOperators::unescapeValue($value);

            $attribute->setProductValue($value, $attributeValues, $rowData['channel'] ?? null, $rowData['locale'] ?? null);
        }
    }
}
