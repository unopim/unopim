<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Slug;

class ProductForm extends FormRequest
{
    /**
     * Determine if the product is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sku' => ['required', 'unique:products,sku,'.$this->id, new Slug],
        ];
    }

    public function prepareForValidation()
    {
        if (isset($this->uniqueFields['values.common.sku']) || isset($this->values['common']['sku'])) {
            $this->merge([
                'sku' => $this->values['common']['sku'],
            ]);
        }
    }
}
