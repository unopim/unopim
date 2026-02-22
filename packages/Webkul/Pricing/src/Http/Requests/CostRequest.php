<?php

namespace Webkul\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'product_id'     => ['required', 'exists:products,id'],
            'cost_type'      => ['required', Rule::in(['cogs', 'operational', 'marketing', 'platform', 'shipping', 'overhead'])],
            'amount'         => ['required', 'numeric', 'min:0'],
            'currency_code'  => ['required', 'string', 'size:3'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['product_id'] = ['sometimes', 'exists:products,id'];
            $rules['cost_type'] = ['sometimes', Rule::in(['cogs', 'operational', 'marketing', 'platform', 'shipping', 'overhead'])];
            $rules['amount'] = ['sometimes', 'numeric', 'min:0'];
            $rules['currency_code'] = ['sometimes', 'string', 'size:3'];
            $rules['effective_from'] = ['sometimes', 'date'];
            $rules['effective_to'] = ['nullable', 'date', 'after_or_equal:effective_from'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'currency_code.size' => trans('pricing::app.costs.validation.currency-code-size'),
            'effective_to.after_or_equal' => trans('pricing::app.costs.validation.effective-to-after'),
        ];
    }
}
