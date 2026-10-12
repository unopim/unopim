<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Category\Repositories\CategoryRepository;

class CategorySearchForm extends FormRequest
{
    /**
     * Determine if the request is authorized to search categories.
     */
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.categories'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query'   => ['nullable', 'string', 'max:255'],
            'codes'   => ['nullable', 'array'],
            'codes.*' => ['string'],
            'page'    => ['nullable', 'integer', 'min:'.CategoryRepository::DEFAULT_PAGE],
            'locale'  => ['nullable', 'string', Rule::exists('locales', 'code')],
        ];
    }

    /**
     * Codes the result set is restricted to, used to list an existing selection.
     *
     * @return string[]
     */
    public function codes(): array
    {
        return array_values(array_unique(array_filter((array) $this->validated('codes', []), 'is_string')));
    }
}
