<?php

declare(strict_types=1);

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassDestroyRequest extends FormRequest
{
    /**
     * Determine if the request is authorized or not.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'indices'   => ['required', 'array'],
            'indices.*' => ['integer'],
        ];
    }
}
