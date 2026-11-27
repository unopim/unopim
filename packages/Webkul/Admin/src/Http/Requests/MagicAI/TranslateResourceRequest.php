<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Webkul\Admin\Http\Requests\MagicAI\Concerns\AuthorizesPromptResource;

class TranslateResourceRequest extends FormRequest
{
    use AuthorizesPromptResource;

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resource_id'   => ['required', 'integer', 'min:1'],
            'resource_type' => ['required', 'in:product,category'],
            'targetLocale'  => ['required', 'string'],
            'model'         => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'model.required' => trans('admin::app.catalog.products.edit.translate.model-required'),
        ];
    }

    /**
     * Keep the unauthorized message the translate endpoints returned before.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(trans('admin::app.common.unauthorized'));
    }
}
