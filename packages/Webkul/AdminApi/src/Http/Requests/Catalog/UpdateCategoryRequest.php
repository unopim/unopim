<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\AdminApi\Http\Requests\Concerns\ValidatesCategoryAdditionalData;

class UpdateCategoryRequest extends ApiFormRequest
{
    use ValidatesCategoryAdditionalData;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->additionalDataRules(),
            'parent' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
