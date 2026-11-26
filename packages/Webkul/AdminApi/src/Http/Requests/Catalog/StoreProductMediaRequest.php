<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

class StoreProductMediaRequest extends ProductMediaRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file'      => 'required',
            'sku'       => 'required',
            'attribute' => 'required',
            ...parent::rules(),
        ];
    }
}
