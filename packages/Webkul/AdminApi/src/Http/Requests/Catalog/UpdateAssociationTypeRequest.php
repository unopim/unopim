<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\AdminApi\Http\Requests\Catalog\Concerns\ValidatesNestedAssociationTypeFields;

class UpdateAssociationTypeRequest extends ApiFormRequest
{
    use ValidatesNestedAssociationTypeFields;

    public function rules(): array
    {
        $rules = [
            'status' => ['sometimes', 'boolean'],
            ...$this->nestedFieldRules(creating: false),
        ];

        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['sometimes', 'nullable', 'string'];
        }

        return $rules;
    }
}
