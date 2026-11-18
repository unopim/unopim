<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Requests\MagicAI\Concerns\GuardsManagedPlatform;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Rules\SafeProviderExtras;

class PlatformTestRequest extends FormRequest
{
    use GuardsManagedPlatform;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'api_key'  => 'nullable|string',
            'api_url'  => 'nullable|string',
            'models'   => 'required|string',
            'extras'   => ['nullable', new SafeProviderExtras],
        ];
    }
}
