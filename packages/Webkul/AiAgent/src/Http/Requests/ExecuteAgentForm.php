<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for agent execution.
 */
class ExecuteAgentForm extends FormRequest
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
        return [
            'agentId'      => 'required|exists:ai_agent_agents,id',
            'credentialId' => 'required|exists:ai_agent_credentials,id',
            'instruction'  => 'required|string|max:50000',
            'context'      => 'nullable|array',
            'async'        => 'sometimes|boolean',
        ];
    }
}
