<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for Agent create/update validation.
 */
class AgentForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent.agents');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'systemPrompt' => 'nullable|string|max:10000',
            'credentialId' => 'required|exists:ai_agent_credentials,id',
            'maxTokens'    => 'sometimes|integer|min:1|max:128000',
            'temperature'  => 'sometimes|numeric|min:0|max:2',
            'pipeline'     => 'nullable|array',
            'status'       => 'sometimes|boolean',
        ];
    }
}
