<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\Code;
use Webkul\Core\Rules\FileOrImageValidValue;

class AttributeOptionForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        $attributeId = $this->route('attribute_id');

        $rules = [
            'code' => [
                'required',
                Rule::unique('attribute_options', 'code')->where(function ($query) use ($attributeId) {
                    return $query->where('attribute_id', $attributeId);
                }),
                new Code,
            ],
            'locales.*.label' => 'nullable|string',
        ];

        if ($this->hasFile('swatch_value')) {
            $rules['swatch_value'] = [new FileOrImageValidValue(isImage: true)];
        }

        return $rules;
    }
}
