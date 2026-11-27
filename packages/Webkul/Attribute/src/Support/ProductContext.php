<?php

namespace Webkul\Attribute\Support;

use Webkul\Attribute\Contracts\DerivedValueResolver;

/**
 * The product data a derived-value implementation may see.
 *
 * Lives in the Attribute package rather than Product because it is part of the
 * {@see DerivedValueResolver} signature, and that
 * contract must not drag a dependency on the Product package into every
 * consumer.
 *
 * Deliberately a plain data holder rather than the Product model: an
 * implementation may hand these values to an expression engine, and the
 * sandboxing such an implementation can offer depends on only scalars and
 * arrays reaching it.
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
     * Build from the flat row a product exporter already assembles, avoiding a
     * second pass over the model inside the export loop.
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
     * Build from the array shape the admin API assembles for one row.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $values  variant-resolved values for the row
     */
    public static function fromApiRow(array $row, array $values): self
    {
        return new self(
            values: $values,
            sku: $row['sku'] ?? null,
            type: $row['type'] ?? null,
            status: (bool) ($row['status'] ?? false),
            family: $row['attribute_family']['code'] ?? null,
            parent: $row['parent']['sku'] ?? null,
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
