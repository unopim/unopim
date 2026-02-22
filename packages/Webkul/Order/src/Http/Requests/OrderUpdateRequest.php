<?php

namespace Webkul\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Order Update Request
 *
 * Validation rules for updating orders (limited to status and notes).
 */
class OrderUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return bouncer()->allows('orders.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:pending,processing,completed,cancelled,refunded',
            ],
            'admin_notes' => [
                'nullable',
                'string',
                'max:1000',
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
            'status' => trans('order::app.admin.orders.fields.status'),
            'admin_notes' => trans('order::app.admin.orders.fields.admin-notes'),
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
            'status.in' => trans('order::app.admin.orders.validation.invalid-status'),
            'admin_notes.max' => trans('order::app.admin.orders.validation.notes-too-long'),
        ];
    }
}
