<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\MagicAI\MagicAI;

class ContentGenerationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     *
     * Expanding a product or category into the prompt reads its stored values,
     * so the matching catalog view permission is required as well.
     */
    public function authorize(): bool
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return false;
        }

        return match ($this->input('resource_type')) {
            'product'  => bouncer()->hasPermission('catalog.products'),
            'category' => bouncer()->hasPermission('catalog.categories'),
            default    => true,
        };
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'model'              => 'required',
            'prompt'             => 'required',
            'system_prompt_text' => ['nullable', 'string', 'max:4000'],
            'temperature'        => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens'         => ['nullable', 'integer', 'min:1', 'max:'.MagicAI::MAX_TOKENS_CEILING],
            'resource_id'        => ['nullable', 'integer', 'min:1', 'required_with:resource_type'],
            'resource_type'      => ['nullable', 'in:product,category', 'required_with:resource_id'],
        ];
    }
}
