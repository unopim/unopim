<?php

namespace Webkul\AdminApi\Http\Requests\Integrations;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateSecretKeyRequest extends FormRequest
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
            'oauth_client_id' => ['required', 'exists:oauth_clients,id'],
        ];
    }
}
