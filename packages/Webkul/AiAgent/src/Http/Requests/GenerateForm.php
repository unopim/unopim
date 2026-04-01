<?php

namespace Webkul\AiAgent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) bouncer()->hasPermission('ai-agent.generate');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images'        => ['required', 'array', 'min:1', 'max:10'],
            'images.*'      => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:10240'],
            'credential_id' => ['required', 'integer', 'exists:ai_agent_credentials,id'],
            'instruction'   => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Custom messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required'        => trans('ai-agent::app.generate.validation.images-required'),
            'images.*.image'         => trans('ai-agent::app.generate.validation.image-invalid'),
            'images.*.max'           => trans('ai-agent::app.generate.validation.image-too-large'),
            'credential_id.required' => trans('ai-agent::app.generate.validation.credential-required'),
        ];
    }
}
