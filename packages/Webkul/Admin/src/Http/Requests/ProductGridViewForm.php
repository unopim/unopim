<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductGridViewForm extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.products');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'is_shared' => ['sometimes', 'boolean'],

            'payload'                       => ['required', 'array'],
            'payload.filters'               => ['present', 'array', 'max:50'],
            'payload.filters.*.index'       => ['required', 'string', 'max:255'],
            'payload.filters.*.value'       => ['present'],
            'payload.activeFilterIndices'   => ['present', 'array', 'max:50'],
            'payload.activeFilterIndices.*' => ['string', 'max:255'],
            'payload.columns'               => ['present', 'array', 'max:100'],
            'payload.columns.*'             => ['string', 'max:255'],
            'payload.sort'                  => ['present', 'array'],
            'payload.sort.column'           => ['nullable', 'string', 'max:255'],
            'payload.sort.order'            => ['nullable', 'in:asc,desc'],
            'payload.perPage'               => ['required', 'integer', 'min:1', 'max:100'],
            'payload.channel'               => ['nullable', 'string', 'max:50'],
            'payload.locale'                => ['nullable', 'string', 'max:50'],
        ];
    }
}
