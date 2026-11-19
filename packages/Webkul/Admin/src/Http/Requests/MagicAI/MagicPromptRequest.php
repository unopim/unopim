<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;

class MagicPromptRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt'  => ['required', 'string', 'max:10000'],
            'title'   => ['required', 'string', 'max:255'],
            'type'    => ['required', 'in:product,category'],
            'purpose' => ['required', 'in:text_generation,image_generation,translation'],
            'tone'    => ['nullable', 'integer', 'exists:magic_ai_system_prompts,id'],
        ];
    }
}
