<?php

namespace Webkul\DataTransfer\Helpers\Importers\ProductAssociation;

use Illuminate\Support\Facades\DB;

/**
 * Resolves the two id lookups the row-per-link association import needs:
 * a product `sku => id` map (SKUStorage-style) and an association
 * type `code => ['id' => int, 'status' => int]` map.
 *
 * Uses raw `DB::table()` queries (no Eloquent hydration) for the same
 * reasons `Product\SKUStorage` does: this runs once per batch across
 * potentially large files.
 */
class Storage
{
    /**
     * Product items contain sku as key and product id as value
     */
    protected array $productItems = [];

    /**
     * Association type items contain code as key and
     * ['id' => int, 'status' => int] as value
     */
    protected array $typeItems = [];

    /**
     * Initialize storage (resets both maps and eagerly loads association types)
     */
    public function init(): void
    {
        $this->productItems = [];
        $this->typeItems = [];

        $this->loadTypes();
    }

    /**
     * Load all association types (id, code, status)
     */
    public function loadTypes(): void
    {
        $types = DB::table('association_types')
            ->select(['id', 'code', 'status'])
            ->get();

        foreach ($types as $type) {
            $this->typeItems[$type->code] = [
                'id'     => (int) $type->id,
                'status' => (int) $type->status,
            ];
        }
    }

    /**
     * Load product ids for the given SKUs, skipping already loaded ones
     * and chunking large lists to avoid query parameter overflow.
     */
    public function loadProducts(array $skus): void
    {
        $skusToLoad = array_values(array_unique(array_filter(
            $skus,
            fn ($sku) => is_string($sku) && $sku !== '' && ! $this->hasProduct($sku)
        )));

        if (empty($skusToLoad)) {
            return;
        }

        foreach (array_chunk($skusToLoad, 1000) as $chunk) {
            $products = DB::table('products')
                ->select(['id', 'sku'])
                ->whereIn('sku', $chunk)
                ->get();

            foreach ($products as $product) {
                $this->productItems[$product->sku] = (int) $product->id;
            }
        }
    }

    /**
     * Check if a product SKU is known
     */
    public function hasProduct(string $sku): bool
    {
        return isset($this->productItems[$sku]);
    }

    /**
     * Get product id for a given SKU
     */
    public function getProductId(string $sku): ?int
    {
        return $this->productItems[$sku] ?? null;
    }

    /**
     * Check if an association type code exists and is active. Lazily loads
     * types if they have not been loaded yet (e.g. when a batch is
     * processed by a fresh instance without an explicit `init()` call).
     */
    public function hasActiveType(string $code): bool
    {
        if (empty($this->typeItems)) {
            $this->loadTypes();
        }

        return isset($this->typeItems[$code]) && $this->typeItems[$code]['status'] === 1;
    }

    /**
     * Get the id for an active association type code
     */
    public function getTypeId(string $code): ?int
    {
        if (empty($this->typeItems)) {
            $this->loadTypes();
        }

        if (! $this->hasActiveType($code)) {
            return null;
        }

        return $this->typeItems[$code]['id'];
    }
}
