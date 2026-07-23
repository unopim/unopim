<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\VariantStructurePlanner as VariantStructurePlannerContract;
use Webkul\Product\Models\VariantStructure;

class VariantStructurePlanner implements VariantStructurePlannerContract
{
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
