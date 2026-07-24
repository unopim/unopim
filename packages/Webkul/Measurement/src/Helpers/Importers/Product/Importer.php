<?php

namespace Webkul\Measurement\Helpers\Importers\Product;

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
        $skipAttributes = array_map(trim(...), $skipAttributes);
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
                $rules[$attributeCode.'(unit)'] = $this->getMeasurementUnitValidationRules($attributeCode, $attribute);
                $rules[$attributeCode.'_unit'] = $this->getMeasurementUnitValidationRules($attributeCode, $attribute);
            }
        }

        return $rules;
    }

    /**
     * Returns validation rules for measurement value columns.
     */
    protected function getMeasurementValueValidationRules(array $validations): array
    {
        $rules = array_values(array_filter($validations, fn ($rule) => $rule !== 'required'));

        if (! in_array('nullable', $rules, true)) {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Returns validation rules for measurement unit columns.
     *
     * The closure rejects any unit that does not belong to the attribute's
     * measurement family, so an unknown/foreign unit fails the row with a message.
     */
    protected function getMeasurementUnitValidationRules(string $attributeCode, $attribute): array
    {
        return [
            'nullable',
            "required_with:{$attributeCode}_value",
            function ($column, $value, $fail) use ($attribute): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (! resolve(MeasurementHelper::class)->isValidUnit($value, $attribute)) {
                    $fail(trans('measurement::app.importers.products.validation.invalid-unit', [
                        'unit'      => $value,
                        'attribute' => $attribute->code,
                    ]));
                }
            },
        ];
    }

    /**
     * Save products from current batch
     */
    public function prepareAttributeValues(array $rowData, array &$attributeValues): void
    {
        $measurementAttributes = $this->getProductTypeFamilyAttributes(
            $rowData['type'],
            $rowData[self::ATTRIBUTE_FAMILY_CODE]
        )->where('type', 'measurement')->keyBy('code');

        parent::prepareAttributeValues(
            $this->withoutMeasurementColumns($rowData, $measurementAttributes),
            $attributeValues
        );

        if ($measurementAttributes->isEmpty()) {
            return;
        }

        $helper = resolve(MeasurementHelper::class);

        foreach ($measurementAttributes as $attributeCode => $attribute) {
            $value = $rowData[$attributeCode] ?? $rowData[$attributeCode.'_value'] ?? null;
            if ($value === null) {
                continue;
            }
            if ($value === '') {
                continue;
            }

            $unit = $rowData[$attributeCode.'(unit)'] ?? $rowData[$attributeCode.'_unit'] ?? null;

            $structured = $this->fieldProcessor->handleField(
                $attribute,
                [
                    'value' => $value,
                    'unit'  => $helper->resolveUnitCode($unit, $attribute, $rowData['locale'] ?? null),
                ],
                $this->import->images_directory_path
            );
            if (! is_array($structured)) {
                continue;
            }
            if (! array_key_exists('amount', $structured)) {
                continue;
            }

            $attribute->setProductValue(
                $structured,
                $attributeValues,
                $rowData['channel'] ?? null,
                $rowData['locale'] ?? null
            );
        }
    }

    /**
     * Strip the measurement value and unit columns so the core importer does not
     * mistake "<code>(unit)" for a currency-scoped column of "<code>".
     */
    protected function withoutMeasurementColumns(array $rowData, $measurementAttributes): array
    {
        foreach ($measurementAttributes as $attributeCode => $attribute) {
            unset(
                $rowData[$attributeCode],
                $rowData[$attributeCode.'_value'],
                $rowData[$attributeCode.'(unit)'],
                $rowData[$attributeCode.'_unit']
            );
        }

        return $rowData;
    }
}
