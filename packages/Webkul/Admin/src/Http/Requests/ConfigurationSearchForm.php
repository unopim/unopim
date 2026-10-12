<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationSearchForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The term is required: `stripos()` treats an empty needle as a match, so a
     * blank search would otherwise return every configuration entry.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
