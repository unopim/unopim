<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Category\Rules\ValidationTypes;

class UpdateAssociationTypeFieldRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $rules = self::fieldRules();

        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.name'] = ['sometimes', 'nullable', 'string'];
        }

        return $rules;
    }

    /**
     * Rules an update of an existing association type field must pass, wherever it is updated from.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function fieldRules(): array
    {
        return [
            'validation'       => ['sometimes', 'nullable', new ValidationTypes],
            'is_required'      => ['sometimes', 'boolean'],
            'is_unique'        => ['sometimes', 'boolean'],
            'value_per_locale' => ['sometimes', 'boolean'],
            'status'           => ['sometimes', 'boolean'],
        ];
    }
}
