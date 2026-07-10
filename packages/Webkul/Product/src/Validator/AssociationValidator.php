<?php

namespace Webkul\Product\Validator;

use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Category\Rules\FieldOption;
use Webkul\Core\Rules\BooleanString;
use Webkul\Product\Contracts\AssociationTypeField;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;

class AssociationValidator
{
    const BOOLEAN_FIELD_TYPE = 'boolean';

    const DATETIME_FIELD_TYPE = 'datetime';

    const DATE_FIELD_TYPE = 'date';

    const SELECT_FIELD_TYPE = 'select';

    const MULTISELECT_FIELD_TYPE = 'multiselect';

    const CHECKBOX_FIELD_TYPE = 'checkbox';

    const FILE_FIELD_TYPE = 'file';

    const IMAGE_FIELD_TYPE = 'image';

    /**
     * Create a new association validator instance.
     */
    public function __construct(
        protected AssociationTypeFieldRepository $associationTypeFieldRepository
    ) {}

    /**
     * Validates the additional data of an association link against the given association
     * type's active custom fields.
     *
     * @param  array  $additionalData  Shape:
     *                                 [
     *                                 'common' => ['code' => 'value'],
     *                                 'locale_specific' => ['en_US' => ['code' => 'value']],
     *                                 ]
     *
     * @throws ValidationException
     */
    public function validate(int $associationTypeId, array $additionalData, ?int $ignoreId = null): void
    {
        $requestData = ['additional_data' => $additionalData];

        $existsFields = $this->getAssociationTypeFields($associationTypeId);

        $this->unknownFieldsValidate($requestData, $existsFields);

        $rules = $this->inputFieldsRules($existsFields, $requestData, $ignoreId);

        Validator::make($requestData, $rules)->validate();
    }

    /**
     * Retrieves the active fields defined for the given association type.
     */
    protected function getAssociationTypeFields(int $associationTypeId): QueueableCollection
    {
        return $this->associationTypeFieldRepository
            ->where(['association_type_id' => $associationTypeId, 'status' => 1])
            ->orderBy('position')
            ->get();
    }

    /**
     * Checks for field codes present in the additional data that are not defined on the
     * association type and throws when any are found.
     *
     * @throws ValidationException
     */
    protected function unknownFieldsValidate(array $requestData, QueueableCollection $existsFields): void
    {
        $requestedFields = $this->getRequestFields($requestData);

        $existingCodes = $existsFields->pluck('code')->toArray();

        $unknownFields = array_diff($requestedFields, $existingCodes);

        if (! empty($unknownFields)) {
            $validator = Validator::make([], []);
            $validator->after(function ($validator) use ($unknownFields) {
                $validator->errors()->add('additional_data', trans('admin::app.catalog.association_types.unknown-fields', ['fields' => implode(', ', $unknownFields)]));
            });

            $validator->validate();
        }
    }

    /**
     * Retrieves the field codes present in the request data.
     */
    protected function getRequestFields(array $requestData): array
    {
        $commonFields = array_key_exists('common', $requestData['additional_data'])
            ? array_keys($requestData['additional_data']['common'])
            : [];

        $localeSpecificFields = [];

        if (array_key_exists('locale_specific', $requestData['additional_data'])) {
            foreach ($requestData['additional_data']['locale_specific'] as $details) {
                $localeSpecificFields = array_unique(array_merge($localeSpecificFields, array_keys($details)));
            }
        }

        return array_merge($localeSpecificFields, $commonFields);
    }

    /**
     * Builds validation rules for the given fields, keyed on the `additional_data` json paths.
     *
     * Intentionally never emits the DB-level `unique:product_associations,...`
     * rule (`withUniqueValidation: false`): that rule is scoped globally
     * across the whole `product_associations` table, which is the wrong
     * semantic for a per-link field value (it has no notion of the
     * `(product, association_type)` pair a link belongs to), and validation
     * here always runs BEFORE the product/link rows are saved, so there is
     * no row id yet to `ignore` on a re-save -- meaning an `is_unique`
     * field's own previously-persisted value would match itself and abort
     * every subsequent save of that same link. Per-link uniqueness is not
     * enforced in this iteration; a proper per-(product, association_type)
     * scoped check can be added later if needed.
     */
    protected function inputFieldsRules(QueueableCollection $existsFields, array $requestData, ?int $id = null): array
    {
        $rules = [];
        $defaultLocale = core()->getRequestedLocaleCode();

        foreach ($existsFields as $field) {
            $fieldName = $field->code;

            if ($field->isLocaleBasedField()) {
                $localeSpecificData = $requestData['additional_data']['locale_specific'] ?? [$defaultLocale => ''];

                foreach (array_keys($localeSpecificData) as $locale) {
                    $fieldNameKey = sprintf('additional_data.locale_specific.%s.%s', $locale, $fieldName);

                    $ruleFormat = $field->getValidationRules($locale, $id, withUniqueValidation: false);
                    $ruleFormat = array_merge($ruleFormat, $this->fieldTypeRules($field));

                    if (! empty($ruleFormat)) {
                        $rules[$fieldNameKey] = $ruleFormat;
                    }
                }
            } else {
                $fieldNameKey = sprintf('additional_data.common.%s', $fieldName);

                $ruleFormat = $field->getValidationRules(null, $id, withUniqueValidation: false);
                $ruleFormat = array_merge($ruleFormat, $this->fieldTypeRules($field));

                if (! empty($ruleFormat)) {
                    $rules[$fieldNameKey] = $ruleFormat;
                }
            }
        }

        return $rules;
    }

    /**
     * Generates extra validation rules for a field based on its type, mirroring
     * `Webkul\Category\Validator\FieldValidator::fieldTypeRules`.
     */
    protected function fieldTypeRules(AssociationTypeField $field): array
    {
        $ruleFormat = [];

        switch ($field->type) {
            case self::BOOLEAN_FIELD_TYPE:
                $ruleFormat[] = new BooleanString;
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
            case self::IMAGE_FIELD_TYPE:
                $ruleFormat[] = 'string';
                break;
        }

        return $ruleFormat;
    }
}
