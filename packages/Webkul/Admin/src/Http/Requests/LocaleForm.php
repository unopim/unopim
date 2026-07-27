<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Code;

class LocaleForm extends FormRequest
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
        // field — a store request could otherwise inject `id` to skip code rules.
        if ($this->isMethod('PUT')) {
            return [
                'status' => ['boolean'],
            ];
        }

        return [
            'code' => ['required', 'unique:locales,code', new Code],
        ];
    }
}
