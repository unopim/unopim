<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Foundation\Http\FormRequest;

class ImageGenerationRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt'        => ['required', 'string', 'max:10000'],
            'model'         => ['required', 'string', 'max:100'],
            'size'          => ['required', 'in:1024x1024,1024x1792,1792x1024'],
            'n'             => ['nullable', 'integer', 'min:1', 'max:10'],
            'quality'       => ['nullable', 'in:standard,hd'],
            'platform_id'   => ['nullable', 'integer', 'min:1'],
            'resource_id'   => ['nullable', 'integer', 'min:1', 'required_with:resource_type'],
            'resource_type' => ['nullable', 'in:product,category', 'required_with:resource_id'],
        ];
    }
}
