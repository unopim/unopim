<?php

namespace Webkul\Product\Listeners;

use Webkul\Attribute\Contracts\AttributeFamily;
use Webkul\Product\Models\VariantStructure;

class CopyVariantStructure
{
    /**
     * Deep-clone the source family's variant structures onto the newly copied family.
     *
     * Laravel's event dispatcher spreads an associative payload array positionally
     * (via `array_values()`) onto class-based listeners, so this receives the
     * `['family' => ..., 'source' => ...]` payload as two ordered arguments rather
     * than a single array.
     */
    public function handle(?AttributeFamily $family = null, ?AttributeFamily $source = null): void
    {
        if (! $family || ! $source) {
            return;
        }

        $structures = VariantStructure::where('attribute_family_id', $source->id)
            ->with(['axes', 'placements'])
            ->get();

        foreach ($structures as $structure) {
            $clone = VariantStructure::create([
                'attribute_family_id' => $family->id,
                'code'                => $family->code.'_'.$structure->code,
                'name'                => $structure->name,
                'levels'              => $structure->levels,
            ]);

            foreach ($structure->axes as $axis) {
                $clone->axes()->create([
                    'attribute_id' => $axis->attribute_id,
                    'position'     => $axis->position,
                ]);
            }

            foreach ($structure->placements as $placement) {
                $clone->placements()->create([
                    'attribute_id' => $placement->attribute_id,
                    'level'        => $placement->level,
                ]);
            }
        }
    }
}
