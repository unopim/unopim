<?php

namespace Webkul\Webhook\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Webhook\Registry\EventRegistry;
use Webkul\Webhook\Validators\SafeWebhookUrl;

class WebhookForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the events payload: the multiselect control may submit a JSON
     * or comma-separated string rather than a native array.
     */
    protected function prepareForValidation(): void
    {
        $events = $this->input('events');

        if (is_string($events)) {
            $decoded = json_decode($events, true);

            $this->merge([
                'events' => is_array($decoded)
                    ? $decoded
                    : array_values(array_filter(array_map(trim(...), explode(',', $events)))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'url'       => [
                'required',
                'string',
                'max:2048',
                'regex:#^https?://#i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! SafeWebhookUrl::validate($value)['valid']) {
                        $fail(trans('webhook::app.webhooks.validation.unsafe-url'));
                    }
                },
            ],
            'is_active' => ['sometimes', 'boolean'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['string', 'in:'.implode(',', $this->allowedEvents())],
            'secret'    => ['nullable', 'string', 'max:255'],
            'headers'   => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => trans('webhook::app.webhooks.validation.scheme'),
        ];
    }

    /**
     * The event keys registered in the webhook event registry.
     *
     * @return array<int, string>
     */
    protected function allowedEvents(): array
    {
        return resolve(EventRegistry::class)->keys();
    }
}
