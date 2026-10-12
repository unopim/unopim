<?php

namespace Webkul\AdminApi\Http\Requests\Settings;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Core\Rules\Code;

class StoreLocaleRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'   => ['required', 'unique:locales,code', new Code],
            'status' => ['sometimes', 'boolean'],
        ];
    }
}
