<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\Code;

class AttributeOptionForm extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $attributeId = $this->route('attribute_id');

        return [
            'code' => [
                'required',
                Rule::unique('attribute_options', 'code')->where(fn (Builder $query) => $query->where('attribute_id', $attributeId)),
                new Code,
            ],
            'locales.*.label' => 'nullable|string',
        ];
    }
}
