<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off data migration: backfills the `product_associations` link table
 * from the legacy `products.values['associations'][section]` JSON so that
 * historical associations (created before Task 4's dual-write existed)
 * are also represented as rows.
 *
 * Legacy sections mirror `Webkul\Product\Type\AbstractType::ASSOCIATION_SECTIONS`
 * (related_products, up_sells, cross_sells). The codes are hardcoded here
 * rather than importing the app class, since migrations should stay
 * self-contained and not depend on application code that may change shape
 * over time.
 */
return new class extends Migration
{
    /**
     * Legacy association sections, in the same order as
     * `AbstractType::ASSOCIATION_SECTIONS`.
     */
    private const SECTIONS = [
        'related_products',
        'up_sells',
        'cross_sells',
    ];

    /**
     * Number of products read per chunk. Kept small and constant so memory
     * stays bounded regardless of catalog size.
     */
    private const CHUNK_SIZE = 200;

    /**
     * Run the migrations.
     *
     * Strategy notes:
     *
     * - `code => association_type_id` map: preloaded ONCE before the chunk
     *   loop. `association_types` only ever holds a handful of rows (3
     *   legacy codes + any user-defined types), so caching it fully in
     *   memory is safe and avoids repeated lookups per product.
     *
     * - `sku => product_id` map: deliberately NOT preloaded as one giant
     *   map. Product catalogs can be very large, and materializing every
     *   SKU up front could itself blow memory on big installs — the exact
     *   problem chunking is meant to avoid. Instead, for each chunk we
     *   collect only the SKUs actually referenced by that chunk's
     *   associations JSON and resolve them with a single `whereIn` query
     *   (bulk, not per-SKU-in-a-loop). This keeps memory bounded while
     *   still doing O(1) queries per chunk rather than O(n) per row.
     *
     * - Products are streamed via the query builder's `chunkById(200)`
     *   (not Eloquent) to avoid the memory/hydration overhead of Product
     *   models for a migration.
     *
     * - Rows are written with a single bulk `upsert()` per chunk, keyed on
     *   the `(product_id, association_type_id, related_product_id)` unique
     *   index (`product_assoc_unique_link`). This is the batched
     *   equivalent of `updateOrInsert` — same idempotency guarantee (a
     *   second run overwrites in place instead of duplicating), but one
     *   query per chunk instead of one query per link.
     */
    public function up(): void
    {
        // code => association_type_id, preloaded once.
        $typeIdByCode = DB::table('association_types')
            ->whereIn('code', self::SECTIONS)
            ->pluck('id', 'code');

        if ($typeIdByCode->isEmpty()) {
            return;
        }

        DB::table('products')
            ->select(['id', 'sku', 'values'])
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($products) use ($typeIdByCode) {
                $this->backfillChunk($products, $typeIdByCode);
            });
    }

    /**
     * Backfill a single chunk of products.
     */
    private function backfillChunk($products, $typeIdByCode): void
    {
        $decoded = [];
        $referencedSkus = [];

        foreach ($products as $product) {
            $values = json_decode((string) $product->values, true) ?: [];

            $associations = $values['associations'] ?? [];

            if (empty($associations) || ! is_array($associations)) {
                continue;
            }

            $decoded[$product->id] = $associations;

            foreach (self::SECTIONS as $section) {
                foreach ((array) ($associations[$section] ?? []) as $sku) {
                    if (is_string($sku) && $sku !== '') {
                        $referencedSkus[$sku] = true;
                    }
                }
            }
        }

        if (empty($decoded) || empty($referencedSkus)) {
            return;
        }

        // Resolve only the SKUs referenced by this chunk, in one bulk query.
        $productIdBySku = DB::table('products')
            ->whereIn('sku', array_keys($referencedSkus))
            ->pluck('id', 'sku');

        $now = now();
        $rows = [];

        foreach ($decoded as $productId => $associations) {
            foreach (self::SECTIONS as $section) {
                $associationTypeId = $typeIdByCode[$section] ?? null;

                if (! $associationTypeId) {
                    continue;
                }

                foreach ((array) ($associations[$section] ?? []) as $sku) {
                    if (! is_string($sku) || $sku === '') {
                        continue;
                    }

                    $relatedProductId = $productIdBySku[$sku] ?? null;

                    // Skip unresolved SKUs and self-links, mirroring the
                    // importer's prepareOtherSections() filtering.
                    if (! $relatedProductId || (int) $relatedProductId === (int) $productId) {
                        continue;
                    }

                    $rows[] = [
                        'product_id'           => $productId,
                        'association_type_id'  => $associationTypeId,
                        'related_product_id'   => (int) $relatedProductId,
                        'position'             => null,
                        'additional_data'      => null,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
            }
        }

        if (empty($rows)) {
            return;
        }

        DB::table('product_associations')->upsert(
            $rows,
            ['product_id', 'association_type_id', 'related_product_id'],
            ['position', 'additional_data', 'updated_at']
        );
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally a no-op. This is a one-off data migration, not a
     * schema change: reversing it would mean deleting rows from
     * `product_associations`, but by the time this migration has run,
     * Task 4's dual-write is already live and continuously writing to the
     * same table from normal product saves. There is no reliable way to
     * distinguish "a row this backfill created" from "a row a legitimate
     * save created since" (both have `additional_data = null`), so
     * attempting to roll back risks deleting live, legitimately
     * dual-written data. Leaving the table as-is on rollback is the safe
     * choice; the link table simply keeps reflecting the (still present)
     * legacy JSON either way.
     */
    public function down(): void
    {
        // Intentionally left blank — see docblock above.
    }
};
