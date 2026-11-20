<?php

namespace Webkul\Admin\Http\Requests\MagicAI\Concerns;

trait AuthorizesPromptResource
{
    /**
     * Determine whether the user may run Magic AI on the requested resource.
     *
     * Expanding a product or category into the prompt reads its stored values,
     * so the matching catalog view permission is required as well.
     */
    public function authorize(): bool
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return false;
        }

        return match ($this->input('resource_type')) {
            'product'  => bouncer()->hasPermission('catalog.products'),
            'category' => bouncer()->hasPermission('catalog.categories'),
            default    => true,
        };
    }
}
