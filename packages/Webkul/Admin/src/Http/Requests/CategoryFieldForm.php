<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Rules\CategoryFieldValidationRules;
use Webkul\Category\Rules\FieldTypes;
use Webkul\Category\Rules\NotSupportedFields;
use Webkul\Core\Rules\Code;

class CategoryFieldForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('validation')) {
            $this->merge(['validation' => CategoryFieldValidationRules::normalize($this->input('validation'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Constrains type/validation/section to their allowed sets so the admin form
     * matches the API's guarantees and cannot persist an unknown field type.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        $type = $id
            ? (app(CategoryFieldRepository::class)->find($id)?->type ?? $this->input('type'))
            : $this->input('type');

        $rules = [
            'code'             => ['required', Rule::unique('category_fields', 'code')->ignore($id), new Code, new NotSupportedFields],
            'type'             => ['required', new FieldTypes],
            'section'          => ['sometimes', Rule::in(['left', 'right'])],
            'status'           => ['sometimes', 'boolean'],
            'position'         => ['sometimes', 'integer', 'min:0'],
            'enable_wysiwyg'   => ['sometimes', 'boolean'],
            'is_required'      => ['sometimes', 'boolean'],
            'is_unique'        => CategoryFieldValidationRules::uniqueFlagRules($type),
            'value_per_locale' => ['sometimes', 'boolean'],
        ];

        return $rules + CategoryFieldValidationRules::for($type, $this->input('validation'));
    }
}
