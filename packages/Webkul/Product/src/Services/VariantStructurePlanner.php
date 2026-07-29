<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\VariantStructurePlanner as VariantStructurePlannerContract;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;

class VariantStructurePlanner implements VariantStructurePlannerContract
{
    const LEVEL_ORDER = [
        'common'     => 0,
        'sub_parent' => 1,
        'variant'    => 2,
    ];

    /** Structure level a product sits at. */
    public function levelOf(Product $product): ?string
    {
        if (! $this->structureFor($product) instanceof VariantStructure) {
            return null;
        }

        return match ($product->type) {
            'configurable'  => 'common',
            'variant_group' => 'sub_parent',
            'simple'        => 'variant',
            default         => null,
        };
    }

    /** Structure governing a product, from its configurable ancestor. */
    public function structureFor(Product $product): ?VariantStructure
    {
        $node = $product;
        $guard = 0;

        while ($node && $guard++ < 10) {
            if ($node->variant_structure_id) {
                return $node->variantStructure;
            }

            $node = $node->parent;
        }

        return null;
    }

    /** Whether an attribute is maintained at or above a product's own level. */
    public function ownsAttribute(Product $product, string $attributeCode): bool
    {
        $structure = $this->structureFor($product);

        if (! $structure instanceof VariantStructure) {
            return true;
        }

        $level = $this->levelOf($product);

        if ($level === null) {
            return true;
        }

        $placement = in_array($attributeCode, $this->allAxisCodes($structure), true)
            ? $this->axisLevelOf($structure, $attributeCode)
            : $this->placementOf($structure, $attributeCode);

        return (self::LEVEL_ORDER[$placement] ?? 0) <= (self::LEVEL_ORDER[$level] ?? 0);
    }

    /** Structure level an axis is fixed at. */
    protected function axisLevelOf(VariantStructure $structure, string $attributeCode): string
    {
        $axis = $structure->axes->first(fn ($row): bool => $row->attribute?->code === $attributeCode);

        if (! $axis) {
            return 'common';
        }

        if ((int) $structure->levels !== 2) {
            return 'variant';
        }

        return $axis->level === 'level_2' ? 'variant' : 'sub_parent';
    }

    public function axisCodesByLevel(VariantStructure $structure): array
    {
        $byLevel = [];

        foreach ($structure->axes->sortBy('position') as $axis) {
            $byLevel[$axis->level][] = $axis->attribute->code;
        }

        return $byLevel;
    }

    public function allAxisCodes(VariantStructure $structure): array
    {
        return $structure->axes->sortBy([['level', 'asc'], ['position', 'asc']])
            ->map(fn ($axis) => $axis->attribute->code)
            ->values()
            ->all();
    }

    public function placementOf(VariantStructure $structure, string $attributeCode): string
    {
        $placement = $structure->placements
            ->first(fn ($row): bool => $row->attribute->code === $attributeCode);

        return $placement->level ?? 'common';
    }

    public function attributeCodesAtLevel(VariantStructure $structure, string $level): array
    {
        return $structure->placements
            ->filter(fn ($row): bool => $row->level === $level)
            ->map(fn ($row) => $row->attribute->code)
            ->values()
            ->all();
    }
}
