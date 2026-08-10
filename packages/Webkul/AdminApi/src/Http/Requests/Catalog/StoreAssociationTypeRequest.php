<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Core\Rules\Code;
use Webkul\Product\Rules\AssociationNotSupportedFields;

class StoreAssociationTypeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $rules = [
            'code' => [
                'required',
                'unique:association_types,code',
                new Code,
                new AssociationNotSupportedFields,
            ],
            'status' => ['sometimes', 'boolean'],
        ];

        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['sometimes', 'nullable', 'string'];
        }

        return $rules;
    }
}
