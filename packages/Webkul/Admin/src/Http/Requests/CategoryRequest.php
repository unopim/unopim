<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Code;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $uniqueRule = 'unique:categories,code';

        if (! empty($this->id)) {
            $uniqueRule .= ','.$this->id;
        }

        $parentRule = ['nullable', 'integer', 'exists:categories,id'];

        if ($this->id) {
            return [
                'code' => [
                    $uniqueRule,
                    new Code,
                ],
                'parent_id' => $parentRule,
            ];
        }

        return [
            'code' => [
                'required',
                $uniqueRule,
                new Code,
            ],
            'parent_id' => $parentRule,
        ];
    }
}
