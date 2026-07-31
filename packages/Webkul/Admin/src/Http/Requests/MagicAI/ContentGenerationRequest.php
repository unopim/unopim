<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;

class ContentGenerationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'model'              => 'required',
            'prompt'             => 'required',
            'system_prompt_text' => ['nullable', 'string', 'max:4000'],
            'temperature'        => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens'         => ['nullable', 'integer', 'min:1', 'max:16384'],
        ];
    }
}
