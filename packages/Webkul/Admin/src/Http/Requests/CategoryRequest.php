<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Code;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the Configuration is authorized to make this request.
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
        if ($this->id) {
            return [
                'code' => [
                    'unique:categories,code,'.$this->id,
                    new Code,
                ],
            ];
        }

        return [
            'code' => [
                'required',
                'unique:categories,code,'.$this->id,
                new Code,
            ],
        ];
    }
}
