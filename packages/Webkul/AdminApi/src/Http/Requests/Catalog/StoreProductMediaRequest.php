<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\Core\Rules\FileNameWithoutComma;

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
            'file' => ['required', new FileNameWithoutComma],
            'sku'  => 'required',
            ...parent::rules(),
        ];
    }
}
