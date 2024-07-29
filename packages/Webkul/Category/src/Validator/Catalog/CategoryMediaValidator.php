<?php

namespace Webkul\Category\Validator\Catalog;

use Illuminate\Support\Facades\Validator;

class CategoryMediaValidator extends CategoryValidator
{
    /**
     * Validates the request data for category media.
     *
     * @param  array  $requestData  The request data containing the category field and file.
     * @param  int|null  $id  The ID of the category being validated (optional).
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator|array
     *                                                                                           Returns an empty array if the validation passes.
     *                                                                                           Returns an instance of Illuminate\Contracts\Validation\Validator if the validation fails.
     *                                                                                           The validator instance contains the validation errors.
     *                                                                                           If the category field is unknown or not related to media, an error message is added to the validator's errors.
     *
     * @example
     * [
     *    'code'           => 'root',
     *    'category_field' => 'picture',
     *    'file'           => 'Illuminate\Http\UploadedFile',
     * ]
     */
    public function validate(array $requestData, ?int $id = null)
    {
        $unknownFieldsValidate = $this->unknownFieldsValidate($requestData);
        if ($unknownFieldsValidate instanceof \Illuminate\Validation\Validator && $unknownFieldsValidate->fails()) {
            return $unknownFieldsValidate;
        }

        $inputFieldsValidate = $this->inputFieldValidate($requestData, $id);
        if ($inputFieldsValidate instanceof \Illuminate\Validation\Validator && $inputFieldsValidate->fails()) {
            return $inputFieldsValidate;
        }

        return [];
    }

    /**
     * Validates the unknown fields in the request data for category media.
     *
     * @param  array  $requestData  The request data containing the category field.
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator The validator instance.
     *                                                                                     If the category field is unknown or not related to media, an error message is added to the validator's errors.
     *                                                                                     If the category field is valid, an empty validator is returned.
     */
    protected function unknownFieldsValidate(array $requestData)
    {
        $unknownFields = $this->getCategoryFields([$requestData['category_field']])->first()?->toArray();
        if (! $unknownFields) {
            $validator = Validator::make([], []);
            $validator->after(function ($validator) use ($requestData) {
                $validator->errors()->add('additional_data', trans('admin::app.catalog.categories.unknown-fields', ['fields' => $requestData['category_field']]));
            });

            return $validator;
        }

        if ($unknownFields && ! in_array($unknownFields['type'], [self::FILE_FIELD_TYPE, self::IMAGE_FIELD_TYPE])) {
            $validator = Validator::make([], []);
            $validator->after(function ($validator) use ($requestData) {
                $validator->errors()->add('additional_data', trans('admin::app.catalog.categories.unknown-media-field', ['fields' => $requestData['category_field']]));
            });

            return $validator;
        }

        return [];
    }

    /**
     * Validates the input fields for category media.
     *
     * @param  array  $requestData  The request data containing the category field and file.
     * @param  int|null  $id  The ID of the category being validated (optional).
     * @return \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator The validator instance.
     */
    protected function inputFieldValidate(array $requestData, ?int $id)
    {
        $rules = [];
        $existsField = $this->getCategoryFields([$requestData['category_field']])->first();
        $rules['file'] = $existsField->getValidationsFieldOnlyMedia();

        return Validator::make($requestData, $rules);
    }
}
