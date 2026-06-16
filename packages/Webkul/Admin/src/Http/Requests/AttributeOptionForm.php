<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\Code;

class AttributeOptionForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $attributeId = $this->route('attribute_id');

        return [
            'code' => [
                'required',
                Rule::unique('attribute_options', 'code')->where(function ($query) use ($attributeId) {
                    return $query->where('attribute_id', $attributeId);
                }),
                new Code,
            ],
            'locales.*.label' => 'nullable|string',
        ];
    }
}
