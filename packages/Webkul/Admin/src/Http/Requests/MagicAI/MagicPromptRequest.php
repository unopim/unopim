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
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'prompt'  => 'required',
            'title'   => 'required',
            'type'    => 'required',
            'purpose' => 'required|in:text_generation,image_generation,translation',
            'tone'    => 'nullable',
        ];
    }
}
