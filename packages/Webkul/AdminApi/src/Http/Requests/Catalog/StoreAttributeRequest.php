<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Rules\NotSupportedAttributes;
use Webkul\Attribute\Rules\SwatchTypes;
use Webkul\Attribute\Rules\ValidationTypes;
use Webkul\Attribute\Support\AttributeValidationRules;
use Webkul\Core\Rules\Code;

class StoreAttributeRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Rules a package contributes for its own attribute type arrive through
     * {@see AttributeValidationRules}, which is the same set the admin form
     * runs — a payload accepted here would be accepted there and vice versa.
     *
     * @return array<string, mixed>
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

        return AttributeValidationRules::extend($rules, $this->all());
    }
}
