<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;

class PlatformRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent.platform');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'label'      => 'required|string|max:255',
            'provider'   => 'required|string',
            'api_url'    => 'nullable|url|max:500',
            'api_key'    => 'nullable|string',
            'models'     => 'required|string',
            'is_default' => 'sometimes|boolean',
            'status'     => 'sometimes|boolean',
        ];
    }
}
