<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttributeOptionSortForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'optionIds'   => ['required', 'array'],
            'optionIds.*' => ['integer'],
            'direction'   => ['required', 'in:up,down'],
            'toIndex'     => ['required', 'integer'],
        ];
    }
}
