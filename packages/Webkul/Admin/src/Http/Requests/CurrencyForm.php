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
        // Discriminate on the HTTP verb (store=POST, update=PUT), never a body
        // field — a store request could otherwise inject `id` to get laxer rules.
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
