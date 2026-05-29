<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Code;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueRule = 'unique:categories,code';

        if (! empty($this->id)) {
            $uniqueRule .= ','.$this->id;
        }

        if ($this->id) {
            return [
                'code' => [
                    $uniqueRule,
                    new Code,
                ],
            ];
        }

        return [
            'code' => [
                'required',
                $uniqueRule,
                new Code,
            ],
        ];
    }
}
