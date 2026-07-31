<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Validation\Rule;
use Webkul\AdminApi\Http\Requests\ApiFormRequest;
use Webkul\Core\Rules\Code;
use Webkul\Core\Rules\FileMimeExtensionMatch;

class StoreSwatchMediaRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                Rule::exists('attribute_options', 'code'),
                new Code,
            ],
            'attribute_code' => [
                'required',
                'string',
                Rule::exists('attributes', 'code'),
                new Code,
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,webp,svg',
                'max:2048',
                new FileMimeExtensionMatch,
            ],
        ];
    }
}
