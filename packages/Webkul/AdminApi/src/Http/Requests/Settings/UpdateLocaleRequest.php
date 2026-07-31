<?php

namespace Webkul\AdminApi\Http\Requests\Settings;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class UpdateLocaleRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'boolean'],
        ];
    }
}
