<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Attribute\Rules\ValidationTypes;
use Webkul\Core\Rules\Code;

class StoreAttributeRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'type' => [
                'required',
                new AttributeTypes,
            ],
            'code' => [
                'required',
                sprintf('unique:%s,code', 'attributes'),
                new Code,
                new NotSupportedAttributes,
            ],
            'swatch_type' => [
                'nullable',
                new SwatchTypes,
            ],
        ];

        if ($this->input('validation')) {
            $rules['validation'] = [new ValidationTypes];
        }

        return $rules;
    }
}
