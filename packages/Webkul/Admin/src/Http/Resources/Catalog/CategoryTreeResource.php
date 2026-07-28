<?php

namespace Webkul\Admin\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * One node as the lazily loaded category tree needs it.
     *
     * Only the fields the tree actually renders are exposed — shipping the raw
     * model would send `additional_data` for every locale and field, which is
     * an order of magnitude larger per node and dominates the payload once a
     * catalogue has thousands of categories.
     *
     * Children come with a `partial` marker: they only cover the branches that
     * had to be revealed, so the tree refetches the level in full the first
     * time it is expanded instead of trusting what it already holds.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $node = [
            'id'        => $this->id,
            'code'      => $this->code,
            'name'      => $this->resolveName($request->input('locale')),
            'parent_id' => $this->parent_id,
            '_lft'      => $this->_lft,
            '_rgt'      => $this->_rgt,
        ];

        if (! $this->relationLoaded('children') || $this->children->isEmpty()) {
            return $node;
        }

        return $node + [
            'partial'  => true,
            'children' => static::collection($this->children)->toArray($request),
        ];
    }

    /**
     * Category names live in `additional_data` keyed by locale, so an unnamed
     * locale falls back to the configured one and then to the code, which keeps
     * a node selectable instead of rendering as an empty row.
     */
    protected function resolveName(?string $locale): string
    {
        $names = $this->additional_data['locale_specific'] ?? [];

        return $names[$locale ?? core()->getRequestedLocaleCode()]['name']
            ?? $names[config('app.fallback_locale')]['name']
            ?? '['.$this->code.']';
    }
}
