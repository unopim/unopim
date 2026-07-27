<?php

namespace Webkul\Admin\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssociationTypeFieldResource extends JsonResource
{
    /**
     * Compact, locale-resolved shape the product-edit Links panel consumes for
     * one association-type field (labels, validation metadata and choice
     * options). Kept identical for both the initial page load and the async
     * type picker so the Vue component can render either the same way.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'code'             => $this->code,
            'type'             => $this->type,
            'label'            => $this->getTranslatedValueWithFallback('name') ?? "[{$this->code}]",
            'is_required'      => (bool) $this->is_required,
            'is_unique'        => (bool) $this->is_unique,
            'value_per_locale' => (bool) $this->value_per_locale,
            'validation'       => $this->validation,
            'regex_pattern'    => $this->regex_pattern,
            'section'          => $this->section,
            'rules'            => $this->getValidationsField(),
            'options'          => $this->options
                ->map(fn ($option) => [
                    'id'    => $option->id,
                    'code'  => $option->code,
                    'label' => $option->getTranslatedValueWithFallback('label') ?? "[{$option->code}]",
                ])
                ->values()
                ->all(),
        ];
    }
}
