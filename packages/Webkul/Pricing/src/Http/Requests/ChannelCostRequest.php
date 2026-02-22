<?php

namespace Webkul\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChannelCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'channel_id'                    => ['required', 'exists:channels,id'],
            'commission_percentage'          => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_fee_per_order'            => ['required', 'numeric', 'min:0'],
            'payment_processing_percentage'  => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_fixed_fee'              => ['required', 'numeric', 'min:0'],
            'shipping_cost_per_zone'         => ['nullable', 'array'],
            'shipping_cost_per_zone.*.zone'  => ['required_with:shipping_cost_per_zone', 'string'],
            'shipping_cost_per_zone.*.cost'  => ['required_with:shipping_cost_per_zone', 'numeric', 'min:0'],
            'currency_code'                  => ['required', 'string', 'size:3'],
            'effective_from'                 => ['required', 'date'],
            'effective_to'                   => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['channel_id'] = ['sometimes', 'exists:channels,id'];
            $rules['commission_percentage'] = ['sometimes', 'numeric', 'min:0', 'max:100'];
            $rules['fixed_fee_per_order'] = ['sometimes', 'numeric', 'min:0'];
            $rules['payment_processing_percentage'] = ['sometimes', 'numeric', 'min:0', 'max:100'];
            $rules['payment_fixed_fee'] = ['sometimes', 'numeric', 'min:0'];
            $rules['currency_code'] = ['sometimes', 'string', 'size:3'];
            $rules['effective_from'] = ['sometimes', 'date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'commission_percentage.max'          => trans('pricing::app.channel-costs.validation.percentage-max'),
            'payment_processing_percentage.max'  => trans('pricing::app.channel-costs.validation.percentage-max'),
            'effective_to.after_or_equal'         => trans('pricing::app.channel-costs.validation.effective-to-after'),
        ];
    }
}
