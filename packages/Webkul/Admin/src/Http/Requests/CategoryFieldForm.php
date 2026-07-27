<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Category\Rules\FieldTypes;
use Webkul\Category\Rules\NotSupportedFields;
use Webkul\Category\Rules\ValidationTypes;
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

    /**
     * Get the validation rules that apply to the request.
     *
     * Constrains type/validation/section to their allowed sets so no unknown field type can be persisted.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        $rules = [
            'code'     => ['required', Rule::unique('category_fields', 'code')->ignore($id), new Code, new NotSupportedFields],
            'type'     => ['required', new FieldTypes],
            'section'  => ['sometimes', Rule::in(['left', 'right'])],
            'status'   => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];

        // Create requires a validation choice; update leaves it untouched.
        if (! $id) {
            $rules['validation'] = ['required'];
        }

        if ($this->filled('validation') && $this->input('validation') !== 'none') {
            $rules['validation'][] = new ValidationTypes;
        }

        return $rules;
    }
}
