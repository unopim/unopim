<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Core\Rules\Code;

class AttributeForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'        => ['required', 'unique:attributes,code', new Code, new NotSupportedAttributes],
            'type'        => ['required', 'string', new AttributeTypes],
            'swatch_type' => [
                'required_if:type,select,multiselect',
                'prohibited_unless:type,select,multiselect',
                new SwatchTypes,
            ],
        ];
    }
}
