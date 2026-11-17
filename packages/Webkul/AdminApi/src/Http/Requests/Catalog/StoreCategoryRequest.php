<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Core\Rules\Code;

class StoreCategoryRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'                              => ['required', 'string', 'unique:categories,code', new Code],
            'additional_data'                   => ['sometimes', 'array'],
            'additional_data.common'            => ['sometimes', 'array'],
            'additional_data.locale_specific'   => ['sometimes', 'array'],
            'additional_data.locale_specific.*' => ['array'],
            'parent'                            => ['sometimes', 'nullable', 'string'],
        ];
    }
}
