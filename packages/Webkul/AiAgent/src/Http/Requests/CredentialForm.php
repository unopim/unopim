<?php

namespace Webkul\AiAgent\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Webhook\Validators\SafeWebhookUrl;

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
            'label'    => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'in:openai,anthropic,azure,custom'],
            'apiUrl'   => ['required', 'url', 'max:500', function (string $attribute, mixed $value, Closure $fail): void {
                if (! SafeWebhookUrl::validate($value)['valid']) {
                    $fail(trans('admin::app.configuration.platform.message.unsafe-api-url'));
                }
            }],
            'apiKey'   => ['required', 'string', 'max:500'],
            'model'    => ['required', 'string', 'max:255'],
            'status'   => ['sometimes', 'boolean'],
        ];
    }
}
