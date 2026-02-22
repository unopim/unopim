<?php

namespace Webkul\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Webhook Update Request
 *
 * Validation rules for updating webhook configurations.
 */
class WebhookUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return bouncer()->allows('orders.webhooks.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $webhookId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('webhooks', 'name')->ignore($webhookId),
            ],
            'channel_id' => [
                'required',
                'integer',
                'exists:channels,id',
            ],
            'endpoint' => [
                'required',
                'url',
                'max:500',
            ],
            'event_types' => [
                'required',
                'array',
                'min:1',
            ],
            'event_types.*' => [
                'required',
                'string',
                'in:order.created,order.updated,order.cancelled,order.refunded,order.fulfilled',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'retry_attempts' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],
            'timeout_seconds' => [
                'nullable',
                'integer',
                'min:1',
                'max:60',
            ],
            'headers' => [
                'nullable',
                'array',
            ],
            'headers.*' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => trans('order::app.admin.webhooks.fields.name'),
            'channel_id' => trans('order::app.admin.webhooks.fields.channel'),
            'endpoint' => trans('order::app.admin.webhooks.fields.endpoint'),
            'event_types' => trans('order::app.admin.webhooks.fields.event-types'),
            'is_active' => trans('order::app.admin.webhooks.fields.is-active'),
            'retry_attempts' => trans('order::app.admin.webhooks.fields.retry-attempts'),
            'timeout_seconds' => trans('order::app.admin.webhooks.fields.timeout'),
            'headers' => trans('order::app.admin.webhooks.fields.headers'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => trans('order::app.admin.webhooks.validation.name-required'),
            'name.unique' => trans('order::app.admin.webhooks.validation.name-unique'),
            'channel_id.exists' => trans('order::app.admin.webhooks.validation.channel-invalid'),
            'endpoint.url' => trans('order::app.admin.webhooks.validation.endpoint-url'),
            'event_types.required' => trans('order::app.admin.webhooks.validation.events-required'),
            'event_types.min' => trans('order::app.admin.webhooks.validation.events-min'),
            'event_types.*.in' => trans('order::app.admin.webhooks.validation.event-invalid'),
        ];
    }
}
