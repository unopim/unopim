<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Rules\Code;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the Configuration is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        if ($this->uniqueFields) {
            foreach ($this->uniqueFields as $fieldName => $fieldNamespace) {
                $pathToValue = str_replace(['additional_data', '[', '][', ']'], ['', '->', '->', ''], $fieldNamespace);

                $rules[$fieldName] = 'unique:categories,additional_data'.$pathToValue;

                if ($this->id) {
                    $rules[$fieldName] = $rules[$fieldName].",{$this->id}";
                }
            }
        }

        if ($this->id) {
            return $rules;
        }

        $rules['code'] = ['required', 'unique:categories,code', new Code];

        return $rules;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        try {
            parent::failedValidation($validator);
        } catch (ValidationException $e) {
            $messages = [];

            $translator = $validator->getTranslator();

            foreach ($validator->errors()->messages() as $key => $message) {
                if (
                    is_string($key)
                    && str_contains($key, 'additional_data')
                    && isset($this->uniqueFields[$key])
                ) {
                    $messages[$this->uniqueFields[$key]] = $translator->get('admin::app.catalog.categories.unique-validation');
                }

                $messages[$key] = $message;
            }

            $e = $e::withMessages($messages);

            throw $e;
        }
    }
}
