<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryTreeForm extends FormRequest
{
    /**
     * Determine if the request is authorized to read the category tree.
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
            'locale'     => ['nullable', 'string', Rule::exists('locales', 'code')],
            'selected'   => ['nullable', 'array'],
            'selected.*' => ['string'],
        ];
    }

    /**
     * Codes of the categories whose branches have to be revealed.
     *
     * @return string[]
     */
    public function selectedCodes(): array
    {
        return array_values(array_unique(array_filter((array) $this->validated('selected', []), 'is_string')));
    }
}
