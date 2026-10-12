<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class UpdateConfigurableProductRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `variant_structure` is create-only and prohibited here rather than silently
     * ignored: repointing it once variants exist would invalidate every descendant's
     * level ownership, as the admin UI also refuses to do.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status'            => ['nullable', 'boolean'],
            'parent'            => ['nullable', 'string'],
            'variant_structure' => ['prohibited'],
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'family'            => ['required', 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['required', 'array'],
            'values.common.sku' => ['required'],
            'associations'      => ['nullable', 'array'],
        ];
    }
}
