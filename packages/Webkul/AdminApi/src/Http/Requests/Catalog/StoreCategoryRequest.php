<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\AdminApi\Http\Requests\Concerns\ValidatesCategoryAdditionalData;
use Webkul\Core\Rules\Code;

class StoreCategoryRequest extends ApiFormRequest
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
            'code'   => ['required', 'string', 'unique:categories,code', new Code],
            ...$this->additionalDataRules(),
            'parent' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
