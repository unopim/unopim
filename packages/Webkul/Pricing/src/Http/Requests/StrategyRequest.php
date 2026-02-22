<?php

namespace Webkul\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrategyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'scope_type'                => ['required', Rule::in(['global', 'category', 'channel', 'product'])],
            'scope_id'                  => ['nullable', 'integer'],
            'minimum_margin_percentage' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'target_margin_percentage'  => ['required', 'numeric', 'min:0', 'max:99.99'],
            'premium_margin_percentage' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'psychological_pricing'     => ['sometimes', 'boolean'],
            'round_to'                  => ['sometimes', Rule::in(['0.99', '0.95', '0.00', 'none'])],
            'is_active'                 => ['sometimes', 'boolean'],
            'priority'                  => ['sometimes', 'integer', 'min:0', 'max:255'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['scope_type'] = ['sometimes', Rule::in(['global', 'category', 'channel', 'product'])];
            $rules['minimum_margin_percentage'] = ['sometimes', 'numeric', 'min:0', 'max:99.99'];
            $rules['target_margin_percentage'] = ['sometimes', 'numeric', 'min:0', 'max:99.99'];
            $rules['premium_margin_percentage'] = ['sometimes', 'numeric', 'min:0', 'max:99.99'];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validated();

            $min = $data['minimum_margin_percentage'] ?? null;
            $target = $data['target_margin_percentage'] ?? null;
            $premium = $data['premium_margin_percentage'] ?? null;

            if ($min !== null && $target !== null && $min >= $target) {
                $validator->errors()->add(
                    'minimum_margin_percentage',
                    trans('pricing::app.strategies.validation.minimum-less-than-target')
                );
            }

            if ($target !== null && $premium !== null && $target >= $premium) {
                $validator->errors()->add(
                    'target_margin_percentage',
                    trans('pricing::app.strategies.validation.target-less-than-premium')
                );
            }

            // Require scope_id when scope_type is not global
            $scopeType = $data['scope_type'] ?? null;
            $scopeId = $data['scope_id'] ?? null;

            if ($scopeType && $scopeType !== 'global' && empty($scopeId)) {
                $validator->errors()->add(
                    'scope_id',
                    trans('pricing::app.strategies.validation.scope-id-required')
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'minimum_margin_percentage.max' => trans('pricing::app.strategies.validation.margin-max'),
            'target_margin_percentage.max'  => trans('pricing::app.strategies.validation.margin-max'),
            'premium_margin_percentage.max' => trans('pricing::app.strategies.validation.margin-max'),
        ];
    }
}
