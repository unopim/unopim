<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Product\Type\AbstractType;

class ProductTemplatePreviewForm extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.products.edit');
    }

    public function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'exists:attributes,code'],
            'values'    => ['nullable', 'array'],

            'values.'.AbstractType::COMMON_VALUES_KEY         => ['nullable', 'array'],
            'values.'.AbstractType::LOCALE_VALUES_KEY         => ['nullable', 'array'],
            'values.'.AbstractType::CHANNEL_VALUES_KEY        => ['nullable', 'array'],
            'values.'.AbstractType::CHANNEL_LOCALE_VALUES_KEY => ['nullable', 'array'],
        ];
    }
}
