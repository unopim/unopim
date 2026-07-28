<?php

namespace Webkul\Admin\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssociationTypeLinkResource extends JsonResource
{
    /**
     * One association type as the product-edit Links panel needs it: code, the
     * locale-resolved name and its active field definitions. Links are added by
     * the caller (they are product-scoped); the async type picker reuses this
     * with no links.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code'   => $this->code,
            'name'   => $this->getTranslatedValueWithFallback('name') ?? "[{$this->code}]",
            'fields' => $this->fields->where('status', 1)->values()
                ->map(fn ($field) => (new AssociationTypeFieldResource($field))->toArray($request))
                ->all(),
        ];
    }
}
