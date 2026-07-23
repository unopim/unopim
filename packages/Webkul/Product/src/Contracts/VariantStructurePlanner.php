<?php

namespace Webkul\Product\Contracts;

use Webkul\Product\Models\VariantStructure;

interface VariantStructurePlanner
{
    /** @return array<string, array<int, string>> */
    public function axisCodesByLevel(VariantStructure $structure): array;

    /** @return array<int, string> */
    public function allAxisCodes(VariantStructure $structure): array;

    public function placementOf(VariantStructure $structure, string $attributeCode): string;

    /** @return array<int, string> */
    public function attributeCodesAtLevel(VariantStructure $structure, string $level): array;
}
