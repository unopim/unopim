<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePassportMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.mapping');
    }

    /**
     * Each mapping value is an optional source-attribute code; an unknown code
     * is rejected so a crafted request can never point a passport field at a
     * non-existent attribute. The keys are `dpp_*` field codes.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'mapping'   => ['nullable', 'array'],
            'mapping.*' => ['nullable', 'string', 'exists:attributes,code'],
        ];
    }
}
