<?php

namespace Webkul\Admin\Http\Requests\MagicAI;

class TranslateAllAttributesRequest extends TranslateResourceRequest
{
    /**
     * Determine whether the user is authorized.
     *
     * The translatable attributes are read from the product itself, so product
     * view is required on top of the permission of the prompt resource.
     */
    public function authorize(): bool
    {
        return parent::authorize()
            && bouncer()->hasPermission('catalog.products');
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'attributes' => ['required', 'string'],
        ];
    }
}
