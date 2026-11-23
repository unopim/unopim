<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Validation\Rule;
use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class StoreProductMediaRequest extends ApiFormRequest
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
            'channel'   => ['nullable', 'string', Rule::exists('channels', 'code')],
            'locale'    => ['nullable', 'string', Rule::exists('locales', 'code')->where('status', 1)],
        ];
    }
}
