<?php

namespace Webkul\Category\Validator;

use Illuminate\Contracts\Queue\QueueableCollection;
use Webkul\Category\Contracts\CategoryField;
use Webkul\Category\Rules\FieldOption;
use Webkul\Core\Rules\BooleanString;

abstract class FieldValidator
{
    const BOOLEAN_FIELD_TYPE = 'boolean';

    const INTEGER_FIELD_TYPE = 'integer';

    const DECIMAL_FIELD_TYPE = 'decimal';

    const TEXT_FIELD_TYPE = 'text';

    const PRICE_FIELD_TYPE = 'price';

    const TEXTAREA_FIELD_TYPE = 'textarea';

    const SELECT_FIELD_TYPE = 'select';

    const MULTISELECT_FIELD_TYPE = 'multiselect';

    const DATETIME_FIELD_TYPE = 'datetime';

    const DATE_FIELD_TYPE = 'date';

    const FILE_FIELD_TYPE = 'file';

    const IMAGE_FIELD_TYPE = 'image';

    const CHECKBOX_FIELD_TYPE = 'checkbox';

    const REGEX_VALIDATION_TYPE = 'regex';

    const NUMBER_VALIDATION_TYPE = 'number';

    const DECIMAL_VALIDATION_TYPE = 'decimal';

    /**
     * Validates the input data based on the category fields.
     *
     * @param  array  $requestData  The input data to be validated. This should contain the category
     *                              field data under the 'additional_data' key.
     * @param  int|null  $id  The ID of the record being validated. This is optional and can be null
     *                        for new records.
     * @return void
     */
    abstract public function validate(array $requestData, ?int $id = null);

    /**
     * This function generates validation rules for a specific category field based on its type.
     *
     * @param  CategoryField  $field  The category field for which the validation rules are to be generated.
     * @return array An array of validation rules for the given category field.
     */
    protected function fieldTypeRules(CategoryField $field): array
    {
        $ruleFormat = [];

        switch ($field->type) {
            case self::BOOLEAN_FIELD_TYPE:
                $ruleFormat[] = new BooleanString();
                break;
            case self::DATETIME_FIELD_TYPE:
                $ruleFormat[] = 'date_format:Y-m-d H:i:s';
                break;
            case self::DATE_FIELD_TYPE:
                $ruleFormat[] = 'date';
                $ruleFormat[] = 'date_format:Y-m-d';

                break;
            case self::SELECT_FIELD_TYPE:
            case self::MULTISELECT_FIELD_TYPE:
            case self::CHECKBOX_FIELD_TYPE:
                $ruleFormat[] = 'string';
                $ruleFormat[] = new FieldOption($field);
                break;
            case self::FILE_FIELD_TYPE:
                $ruleFormat[] = 'string';
                break;
            case self::IMAGE_FIELD_TYPE:
                $ruleFormat[] = 'string';
                break;
        }

        return $ruleFormat;
    }

    /**
     * This function generates validation rules for input fields based on the given fields and request data.
     *
     * @param  QueueableCollection  $existsFields  A collection of existing fields to be validated.
     * @param  array  $requestData  The request data containing additional data to be validated.
     * @param  int|null  $id  The ID of the record being validated (optional).
     * @return array An array of validation rules for the input fields.
     */
    protected function inputFieldsRules(QueueableCollection $existsFields, array $requestData, ?int $id = null): array
    {
        $rules = [];
        $defaultLocale = core()->getRequestedLocaleCode();

        foreach ($existsFields as $key => $field) {
            $fieldName = $field->code;
            $ruleFormat = $field->getValidationsFieldWithOutMedia();
            $ruleFormat = array_merge($ruleFormat, $this->fieldTypeRules($field));
            $uniqueFieldRule = $field->getValidationUniqueField();

            if ($field->value_per_locale) {
                $localeSpecificData = $requestData['additional_data']['locale_specific'] ?? [$defaultLocale => ''];
                foreach (array_keys($localeSpecificData) as $locale) {
                    $fieldNameKey = sprintf('additional_data.locale_specific.%s.%s', $locale, $fieldName);
                    if (! empty($ruleFormat)) {
                        $rules[$fieldNameKey] = $ruleFormat;
                    }

                    // Apply unique validation rule
                    if ($uniqueFieldRule) {
                        $uniqueFieldNameKey = sprintf($uniqueFieldRule, $locale, $fieldName);
                        $rules[$fieldNameKey][] = $id ? $uniqueFieldNameKey.','.$id : $uniqueFieldNameKey;
                    }
                }
            } else {
                $fieldNameKey = sprintf('additional_data.common.%s', $fieldName);
                if (! empty($ruleFormat)) {
                    $rules[$fieldNameKey] = $ruleFormat;
                }

                // Apply unique validation rule
                if ($uniqueFieldRule) {
                    $uniqueFieldNameKey = sprintf($uniqueFieldRule, $fieldName);
                    $rules[$fieldNameKey][] = $id ? $uniqueFieldNameKey.','.$id : $uniqueFieldNameKey;
                }
            }
        }

        return $rules;
    }
}
