<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\MagicAI\Models\MagicAISystemPrompt;

class SystemPromptRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent.system-prompt');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'       => ['required'],
            'tone'        => ['required'],
            'is_enabled'  => ['required', 'boolean'],
            'max_tokens'  => ['required', 'integer', MagicAISystemPrompt::maxTokensRangeRule()],
            'temperature' => ['required', 'numeric', 'between:0,2'],
        ];
    }
}
