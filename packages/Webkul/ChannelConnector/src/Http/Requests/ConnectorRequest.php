<?php

namespace Webkul\ChannelConnector\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $connectorCode = $this->route('code');
        $tenantId = core()->getCurrentTenantId();

        $uniqueRule = Rule::unique('channel_connectors', 'code');

        if ($tenantId) {
            $uniqueRule->where('tenant_id', $tenantId);
        }

        if ($connectorCode) {
            $uniqueRule->ignore($connectorCode, 'code');
        }

        $rules = [
            'code'         => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/', $uniqueRule],
            'name'         => ['required', 'string', 'max:255'],
            'channel_type' => ['required', Rule::in([
                'shopify', 'salla', 'easyorders', 'amazon',
                'woocommerce', 'ebay', 'magento2', 'noon',
            ])],
            'credentials'  => ['required', 'array'],
            'status'       => ['sometimes', Rule::in(['connected', 'disconnected', 'error'])],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['code'] = ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/'];
            $rules['name'] = ['sometimes', 'string', 'max:255'];
            $rules['channel_type'] = ['sometimes', Rule::in([
                'shopify', 'salla', 'easyorders', 'amazon',
                'woocommerce', 'ebay', 'magento2', 'noon',
            ])];
            $rules['credentials'] = ['sometimes', 'array'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'code.regex' => trans('channel_connector::app.connectors.fields.code').' must contain only lowercase letters, numbers, hyphens, and underscores.',
        ];
    }
}
