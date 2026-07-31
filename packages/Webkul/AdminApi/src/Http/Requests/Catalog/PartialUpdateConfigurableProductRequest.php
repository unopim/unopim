<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class PartialUpdateConfigurableProductRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status'       => ['nullable', 'boolean'],
            'additional'   => ['nullable', 'array'],
            'values'       => ['nullable', 'array'],
            'associations' => ['nullable', 'array'],
        ];
    }
}
