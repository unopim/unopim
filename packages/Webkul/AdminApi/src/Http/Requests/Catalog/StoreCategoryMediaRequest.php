<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\Core\Rules\FileNameWithoutComma;

class StoreCategoryMediaRequest extends CategoryMediaRequest
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
            ...parent::rules(),
        ];
    }
}
