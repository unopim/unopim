<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for Credential create/update validation.
 */
class CredentialForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent.credentials');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label'    => 'required|string|max:255',
            'provider' => 'required|string|in:openai,anthropic,azure,custom',
            'apiUrl'   => 'required|url|max:500',
            'apiKey'   => 'required|string|max:500',
            'model'    => 'required|string|max:255',
            'status'   => 'sometimes|boolean',
        ];
    }
}
