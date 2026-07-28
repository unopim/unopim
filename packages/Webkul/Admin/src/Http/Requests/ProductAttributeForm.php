<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductAttributeForm extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.products'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'productId' => ['required', 'integer', Rule::exists('products', 'id')],
        ];
    }
}
