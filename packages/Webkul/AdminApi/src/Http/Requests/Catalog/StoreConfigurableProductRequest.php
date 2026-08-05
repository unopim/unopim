<?php

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Illuminate\Validation\Rule;
use Webkul\AdminApi\Http\Requests\ApiFormRequest;

class StoreConfigurableProductRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * `super_attributes` stops being required once `variant_structure`
     * references an existing structure: the controller then derives the axis
     * codes from that structure, so demanding both would force the client to
     * restate what the structure already declares.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isVariantGroup = $this->input('type') === 'variant_group';

        $hasVariantStructure = filled($this->input('variant_structure'));

        return [
            'status'            => ['nullable', 'boolean'],
            'type'              => ['nullable', Rule::in(['configurable', 'variant_group'])],
            'parent'            => [Rule::requiredIf($isVariantGroup), 'nullable', 'string'],
            'channel'           => ['nullable', 'string'],
            'locale'            => ['nullable', 'string'],
            'family'            => [Rule::requiredIf(! $isVariantGroup), 'string'],
            'additional'        => ['nullable', 'array'],
            'values'            => ['required', 'array'],
            'values.common.sku' => ['required'],
            'variant_structure' => ['nullable', 'string'],
            'super_attributes'  => [Rule::requiredIf(! $isVariantGroup && ! $hasVariantStructure), 'array'],
            'associations'      => ['nullable', 'array'],
        ];
    }
}
