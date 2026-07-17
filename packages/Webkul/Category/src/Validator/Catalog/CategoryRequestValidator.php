<?php

namespace Webkul\Category\Validator\Catalog;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Validator;
use Webkul\Category\Contracts\CategoryField;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Rules\FieldOption;
use Webkul\Core\Rules\Code;
use Webkul\Core\Rules\FileOrImageValidValue;

class CategoryRequestValidator extends CategoryValidator
{
    protected ?int $categoryId = null;

    /**
     * Validates the category data based on the provided request data and optional category ID.
     */
    public function validate(array $requestData, ?int $id = null): void
    {
        $validator = parent::validate($requestData, $id);

        if ($validator instanceof Validator && $validator->fails()) {
            $exception = $validator->getException();

            $errorMessages = [];

            foreach ($validator->errors()->messages() as $key => $message) {
                $messageKey = str_replace(['.', CategoryRepository::ADDITIONAL_VALUES_KEY.']'], ['][', ''], $key);

                $messageKey = CategoryRepository::ADDITIONAL_VALUES_KEY.$messageKey.']';

                $errorMessages[$messageKey] = $message;
            }

            throw $exception::withMessages($errorMessages);
        }
    }

    /**
     * Validates the input fields of the category based on the provided request data and category ID.
     */
    protected function inputFieldValidate(array $requestData, ?int $id)
    {
        if (! array_key_exists(CategoryRepository::ADDITIONAL_VALUES_KEY, $requestData)) {
            return [];
        }

        $existsFields = $this->getCategoryFields();
        $this->categoryId = $id;

        try {
            $rules = $this->inputFieldsRules($existsFields, $requestData, $id);
        } finally {
            $this->categoryId = null;
        }

        $fieldKeys = [];

        foreach ($rules as $key => $validationRules) {
            if (! is_string($key)) {
                continue;
            }

            if (str_contains($key, '.')) {
                $displayKey = explode('.', $key);

                $displayKey = end($displayKey);

                $fieldKeys[$key] = $displayKey;
            }
        }

        if (! $id) {
            $rules['code'] = ['required', 'unique:categories,code', new Code];
        }

        return ValidatorFacade::make($requestData, $rules, [], $fieldKeys);
    }

    protected function fieldTypeRules(CategoryField $field): array
    {
        $rules = parent::fieldTypeRules($field);

        if ($field->type === self::FILE_FIELD_TYPE || $field->type === self::IMAGE_FIELD_TYPE) {
            $maxKilobytes = $field->type === self::IMAGE_FIELD_TYPE
                ? (int) (core()->getConfigData('catalog.categories.fields.image_attribute_upload_size') ?? 2048)
                : (int) (core()->getConfigData('catalog.categories.fields.file_attribute_upload_size') ?? 2048);

            $rules = [new FileOrImageValidValue(
                isImage: $field->type === self::IMAGE_FIELD_TYPE,
                maxKilobytes: $maxKilobytes,
                allowedPathPrefixes: $this->categoryId ? ['category/'.$this->categoryId.'/'.$field->code] : [],
            )];
        }

        if ($field->type === self::CHECKBOX_FIELD_TYPE) {
            $rules = [new FieldOption($field)];
        }

        return $rules;
    }
}
