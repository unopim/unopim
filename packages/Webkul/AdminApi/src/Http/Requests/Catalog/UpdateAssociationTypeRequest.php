<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class UpdateAssociationTypeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $rules = [
            'status' => ['sometimes', 'boolean'],
        ];

        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['sometimes', 'nullable', 'string'];
        }

        return $rules;
    }
}
