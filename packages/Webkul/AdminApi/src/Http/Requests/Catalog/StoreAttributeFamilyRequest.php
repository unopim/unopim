<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Core\Rules\Code;

class StoreAttributeFamilyRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'                                 => ['required', 'unique:attribute_families,code', new Code],
            'attribute_groups'                     => ['sometimes', 'array'],
            'attribute_groups.*.code'              => ['required_with:attribute_groups', 'string'],
            'attribute_groups.*.custom_attributes' => ['sometimes', 'array'],
        ];
    }
}
