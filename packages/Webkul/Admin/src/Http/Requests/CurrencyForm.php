<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\Code;

class CurrencyForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Discriminate on the HTTP verb, not a body field, so a store request can't inject `id` for laxer rules.
        if ($this->isMethod('PUT')) {
            return [
                'code'   => ['required', Rule::unique('currencies', 'code')->ignore($this->input('id')), new Code],
                'status' => ['boolean'],
            ];
        }

        return [
            'code' => ['required', 'min:3', 'max:3', 'unique:currencies,code'],
        ];
    }
}
