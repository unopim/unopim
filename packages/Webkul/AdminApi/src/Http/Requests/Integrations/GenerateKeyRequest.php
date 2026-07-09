<?php

namespace Webkul\AdminApi\Http\Requests\Integrations;

use Illuminate\Foundation\Http\FormRequest;

class GenerateKeyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => 'required',
            'admin_id' => 'required',
            'apiId'    => 'required',
        ];
    }
}
