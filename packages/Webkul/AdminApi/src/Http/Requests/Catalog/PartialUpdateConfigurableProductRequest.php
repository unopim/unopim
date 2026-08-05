<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class PartialUpdateConfigurableProductRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `variant_structure` is create-only and therefore prohibited here rather
     * than silently ignored: repointing a product's structure once variants
     * exist would invalidate every descendant's level ownership, and the admin
     * UI likewise refuses to change a structure that already has variants.
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
