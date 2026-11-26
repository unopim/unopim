<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Admin\Http\Requests\MagicAI\Concerns\AuthorizesPromptResource;
use Webkul\MagicAI\MagicAI;

class ContentGenerationRequest extends FormRequest
{
    use AuthorizesPromptResource;

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'model'              => ['required', 'string', 'max:100'],
            'prompt'             => ['required', 'string', 'max:10000'],
            'system_prompt_text' => ['nullable', 'string', 'max:4000'],
            'temperature'        => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens'         => ['nullable', 'integer', 'min:1', 'max:'.MagicAI::MAX_TOKENS_CEILING],
            'resource_id'        => ['nullable', 'integer', 'min:1', 'required_with:resource_type'],
            'resource_type'      => ['nullable', 'in:product,category', 'required_with:resource_id'],
        ];
    }
}
