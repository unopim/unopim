<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Validation\Rule;
use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class CategoryMediaRequest extends ApiFormRequest
{
    /**
     * Category field types whose values are media files.
     */
    public const array MEDIA_FIELD_TYPES = ['image', 'file'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code'           => ['required', 'string'],
            'category_field' => ['required', 'string', Rule::exists('category_fields', 'code')->whereIn('type', self::MEDIA_FIELD_TYPES)],
        ];
    }
}
