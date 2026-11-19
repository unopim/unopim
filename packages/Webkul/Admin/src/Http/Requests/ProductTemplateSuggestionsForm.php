<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductTemplateSuggestionsForm extends FormRequest
{
    /**
     * Determine if the user is authorized to list product template suggestions.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.products.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'query'     => ['nullable', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:255'],
            'attribute' => ['nullable', 'string', 'max:255'],
        ];
    }
}
