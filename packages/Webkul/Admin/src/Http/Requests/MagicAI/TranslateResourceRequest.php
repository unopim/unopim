<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class TranslateResourceRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     *
     * The prompt is expanded with the stored values of the requested resource,
     * so the view permission of that resource type is required.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('ai-agent')
            && bouncer()->hasPermission($this->resourcePermission());
    }

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
        ];
    }

    /**
     * The catalog view permission that guards the requested resource type.
     */
    protected function resourcePermission(): string
    {
        return $this->input('resource_type') === 'category'
            ? 'catalog.categories'
            : 'catalog.products';
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
