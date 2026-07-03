<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Category\Rules\FieldTypes;
use Webkul\Category\Rules\NotSupportedFields;
use Webkul\Category\Rules\ValidationTypes;
use Webkul\Core\Rules\Code;

class StoreCategoryFieldRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'code' => [
                'required',
                sprintf('unique:%s,code', 'category_fields'),
                new Code,
                new NotSupportedFields,
            ],
            'type' => [
                'required',
                new FieldTypes,
            ],
        ];

        if ($this->input('validation')) {
            $rules['validation'] = [new ValidationTypes];
        }

        return $rules;
    }
}
