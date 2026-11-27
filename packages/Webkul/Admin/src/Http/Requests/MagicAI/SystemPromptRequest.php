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
        $permission = $this->routeIs('admin.magic_ai.system_prompt.store')
            ? 'ai-agent.system-prompt.create'
            : 'ai-agent.system-prompt.edit';

        return bouncer()->hasPermission($permission);
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
            'max_tokens'  => MagicAISystemPrompt::maxTokensRules(),
            'temperature' => ['required', 'numeric', 'between:0,2'],
        ];
    }
}
