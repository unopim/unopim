<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class UpdateAttributeGroupRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'labels' => ['sometimes', 'array'],
        ];
    }
}
