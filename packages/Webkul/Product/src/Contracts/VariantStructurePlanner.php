<?php

namespace Webkul\Product\Contracts;

use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;

interface VariantStructurePlanner
{
    public function levelOf(Product $product): ?string;

    public function structureFor(Product $product): ?VariantStructure;

    public function primeStructure(int $productId, ?VariantStructure $structure): void;

    /** @param  iterable<Product>  $products */
    public function primeStructuresFor(iterable $products): void;

    public function ownsAttribute(Product $product, string $attributeCode): bool;

    public function ownsAtOwnLevel(Product $product, string $attributeCode): bool;

    /** @return array<string, array<int, string>> */
    public function axisCodesByLevel(VariantStructure $structure): array;

    /** @return array<int, string> */
    public function allAxisCodes(VariantStructure $structure): array;

    public function placementOf(VariantStructure $structure, string $attributeCode): string;

    /** @return array<int, string> */
    public function attributeCodesAtLevel(VariantStructure $structure, string $level): array;
}
