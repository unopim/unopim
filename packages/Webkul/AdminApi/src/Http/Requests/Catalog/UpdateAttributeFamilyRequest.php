<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class UpdateAttributeFamilyRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'attribute_groups'                     => ['sometimes', 'array'],
            'attribute_groups.*.code'              => ['required_with:attribute_groups', 'string'],
            'attribute_groups.*.custom_attributes' => ['sometimes', 'array'],
        ];
    }
}
