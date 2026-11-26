<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Admin\Http\Requests\MagicAI\Concerns\AuthorizesPlatformCredentials;
use Webkul\Admin\Http\Requests\MagicAI\Concerns\GuardsManagedPlatform;
use Webkul\MagicAI\Rules\SafeProviderExtras;

class PlatformTestRequest extends FormRequest
{
    use AuthorizesPlatformCredentials;
    use GuardsManagedPlatform;

    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return $this->canUsePlatformCredentials();
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => 'required|string',
            'api_key'  => 'nullable|string',
            'api_url'  => 'nullable',
            'models'   => 'required|string',
            'extras'   => ['nullable', new SafeProviderExtras],
        ];
    }
}
