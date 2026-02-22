<?php

namespace Webkul\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Webhook Receive Request
 *
 * Validation rules for incoming webhook payloads.
 * Minimal validation as each platform has different structures.
 */
class WebhookReceiveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Public endpoint - authorization handled via HMAC signature
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Minimal validation - structure varies by platform
            'event' => 'sometimes|string|max:255',
            'data' => 'sometimes|array',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Log validation failures for debugging
        \Illuminate\Support\Facades\Log::warning('Webhook validation failed', [
            'channel' => $this->route('channelCode'),
            'errors' => $validator->errors()->toArray(),
            'payload' => $this->all(),
        ]);

        parent::failedValidation($validator);
    }
}
