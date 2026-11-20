<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Webkul\Admin\Http\Requests\MagicAI\Concerns\GuardsManagedPlatform;
use Webkul\MagicAI\Validator\PlatformValidator;

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
     * Get the validation rules, reusing the platform form's rules for the
     * fields a connection test receives.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::only(app(PlatformValidator::class)->rules(), ['provider', 'api_key', 'api_url', 'models', 'extras']);
    }
}
