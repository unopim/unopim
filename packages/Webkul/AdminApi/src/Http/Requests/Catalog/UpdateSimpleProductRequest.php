<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class UpdateSimpleProductRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status'            => ['nullable', 'boolean'],
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'parent'            => ['nullable', 'string'],
            'family'            => ['required', 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['required', 'array'],
            'values.common.sku' => ['required'],
        ];
    }
}
