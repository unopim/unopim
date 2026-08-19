<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class PartialUpdateConfigurableProductRequest extends ApiFormRequest
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
            'additional'        => ['nullable', 'array'],
            'values'            => ['nullable', 'array'],
            'variant_structure' => ['prohibited'],
            'associations'      => ['nullable', 'array'],
        ];
    }
}
