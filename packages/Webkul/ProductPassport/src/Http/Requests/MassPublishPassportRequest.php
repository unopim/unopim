<?php

namespace Webkul\ProductPassport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassPublishPassportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('catalog.passport.publish');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'indices'   => ['required', 'array', 'min:1'],
            'indices.*' => ['integer', 'exists:products,id'],
        ];
    }
}
