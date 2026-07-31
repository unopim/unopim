<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class RedactPassportApiRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
