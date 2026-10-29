<?php

namespace Webkul\CalculatedAttribute\Support;

/**
 * The product data a formula may see.
 *
 * Deliberately a plain data holder rather than the Product model. The
 * expression grammar can read properties and call methods on anything placed in
 * the evaluation context, so the sandbox depends on only scalars and arrays
 * reaching it — handing the resolver a model would hand formulas the whole
 * Eloquent surface.
 */
final class ProductContext
{
    /**
     * @param  array<string, mixed>  $values  variant-resolved product values
     * @param  array<int, string>  $categories  assigned category codes
     */
    public function __construct(
        public readonly array $values = [],
        public readonly ?string $sku = null,
        public readonly ?string $type = null,
        public readonly bool $status = false,
        public readonly ?string $family = null,
        public readonly ?string $parent = null,
        public readonly array $categories = [],
    ) {}

    /**
     * Build from a product model, resolving variant inheritance so a variant
     * sees the values it actually renders with.
     */
    public static function fromProduct(object $product): self
    {
        $values = method_exists($product, 'resolvedValues')
            ? $product->resolvedValues()
            : (array) ($product->values ?? []);

        return new self(
            values: $values,
            sku: $product->sku ?? null,
            type: $product->type ?? null,
            status: (bool) ($product->status ?? false),
            family: $product->attribute_family?->code ?? null,
            parent: $product->parent?->sku ?? null,
            categories: self::categoriesFrom($values),
        );
    }

    /**
     * Build from the flat row the product exporter already assembles, avoiding
     * a second pass over the model inside the export loop.
     *
     * @param  array<string, mixed>  $rowData
     * @param  array<string, mixed>  $values  pre-resolved values for the row
     */
    public static function fromExportRow(array $rowData, array $values): self
    {
        return new self(
            values: $values,
            sku: $rowData['sku'] ?? null,
            type: $rowData['type'] ?? null,
            status: (bool) ($rowData['status'] ?? false),
            family: $rowData['attribute_family']['code'] ?? null,
            parent: $rowData['parent'] ?? null,
            categories: self::categoriesFrom($values),
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    private static function categoriesFrom(array $values): array
    {
        $categories = $values['categories'] ?? [];

        if (! is_array($categories)) {
            return [];
        }

        return array_values(array_filter($categories, 'is_string'));
    }
}
