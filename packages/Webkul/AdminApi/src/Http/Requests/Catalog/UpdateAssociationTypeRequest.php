<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Category\Rules\ValidationTypes;

class UpdateAssociationTypeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $rules = [
            'status'              => ['sometimes', 'boolean'],
            'fields'              => ['sometimes', 'array'],
            'fields.*'            => ['array'],
            'fields.*.validation' => ['sometimes', 'nullable', new ValidationTypes],
        ];

        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['sometimes', 'nullable', 'string'];
        }

        return $rules;
    }
}
