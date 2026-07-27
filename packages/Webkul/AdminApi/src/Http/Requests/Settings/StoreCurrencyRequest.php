<?php

namespace Webkul\AdminApi\Http\Requests\Settings;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class StoreCurrencyRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'    => ['required', 'string', 'min:3', 'max:3', 'unique:currencies,code'],
            'symbol'  => ['nullable', 'string'],
            'decimal' => ['nullable', 'integer', 'min:0'],
            'status'  => ['sometimes', 'boolean'],
        ];
    }
}
