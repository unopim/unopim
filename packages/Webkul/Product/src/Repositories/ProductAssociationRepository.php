<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\ProductAssociation;

class ProductAssociationRepository extends Repository
{
    /**
     * Create a new product association repository instance
     */
    public function __construct(
        Container $container,
        protected AssociationTypeRepository $associationTypeRepository,
        protected ProductRepository $productRepository
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return ProductAssociation::class;
    }

    /**
     * Retrieve all association links for a source product with the
     * association type and related product eager loaded (no N+1).
     */
    public function getLinksForProduct(int $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->with(['associationType', 'relatedProduct'])
            ->get();
    }

    /**
     * Replace all links for a given `(productId, associationTypeId)` pair
     * with the provided `$links` set.
     *
     * Each link is `['related_product_id' => int, 'position' => ?int, 'additional_data' => ?array]`.
     *
     * Rows no longer present in `$links` are deleted, changed rows are
     * updated, and new rows are inserted, all inside a single transaction.
     *
     * The caller is authoritative for `additional_data`: surviving rows have
     * it overwritten with whatever the payload provides (null clears it).
     * This is the rich, UI-facing entry point; kept as `syncType` for
     * backward compatibility alongside `syncTypeWithData` below.
     */
    public function syncType(int $productId, int $associationTypeId, array $links): void
    {
        $this->syncLinks($productId, $associationTypeId, $links, preserveAdditionalData: false);
    }

    /**
     * Rich sync used by the association UI: writes `additional_data` for
     * each link from the payload (the UI is authoritative for a type it
     * submitted). Functionally identical to `syncType` today, exposed under
     * its own name so call sites can be explicit about intent.
     *
     * Each link is `['related_product_id' => int, 'position' => ?int, 'additional_data' => ?array]`.
     */
    public function syncTypeWithData(int $productId, int $associationTypeId, array $links): void
    {
        $this->syncLinks($productId, $associationTypeId, $links, preserveAdditionalData: false);
    }

    /**
     * Shared transactional core for `syncType`/`syncTypeWithData` and the
     * legacy `syncFromSkuList` path.
     *
     * Rows no longer present in `$links` are deleted, new rows are
     * inserted, and surviving rows have their `position` updated. Whether
     * `additional_data` is also overwritten on surviving rows depends on
     * `$preserveAdditionalData`:
     *
     * - `false` (default): the payload is authoritative — surviving rows'
     *   `additional_data` is set to whatever `$links` provides (including
     *   null, which clears it). Used by the rich UI sync path.
     * - `true`: surviving rows' existing `additional_data` is left
     *   untouched, since the caller (the legacy dual-write path) never
     *   carries per-link custom-field data and must not wipe out values the
     *   UI previously wrote. New rows are still inserted with whatever
     *   `additional_data` the payload provides (typically null).
     */
    protected function syncLinks(int $productId, int $associationTypeId, array $links, bool $preserveAdditionalData): void
    {
        Event::dispatch('product_association.sync.before', [$productId, $associationTypeId, $links]);

        DB::transaction(function () use ($productId, $associationTypeId, $links, $preserveAdditionalData) {
            $existingLinks = $this->model
                ->where('product_id', $productId)
                ->where('association_type_id', $associationTypeId)
                ->get()
                ->keyBy('related_product_id');

            $incomingRelatedProductIds = collect($links)->pluck('related_product_id')->all();

            $relatedProductIdsToDelete = $existingLinks->keys()->diff($incomingRelatedProductIds);

            if ($relatedProductIdsToDelete->isNotEmpty()) {
                $this->model
                    ->where('product_id', $productId)
                    ->where('association_type_id', $associationTypeId)
                    ->whereIn('related_product_id', $relatedProductIdsToDelete->all())
                    ->delete();
            }

            foreach ($links as $link) {
                $relatedProductId = (int) $link['related_product_id'];
                $position = $link['position'] ?? null;
                $additionalData = $link['additional_data'] ?? null;

                $existingLink = $existingLinks->get($relatedProductId);

                if ($existingLink) {
                    $attributesToUpdate = [];

                    if ($existingLink->position !== $position) {
                        $attributesToUpdate['position'] = $position;
                    }

                    if (! $preserveAdditionalData && $existingLink->additional_data !== $additionalData) {
                        $attributesToUpdate['additional_data'] = $additionalData;
                    }

                    if (! empty($attributesToUpdate)) {
                        $existingLink->update($attributesToUpdate);
                    }

                    continue;
                }

                $this->create([
                    'product_id'          => $productId,
                    'association_type_id' => $associationTypeId,
                    'related_product_id'  => $relatedProductId,
                    'position'            => $position,
                    'additional_data'     => $additionalData,
                ]);
            }
        });

        Event::dispatch('product_association.sync.after', [$productId, $associationTypeId, $links]);
    }

    /**
     * Convenience wrapper used by the legacy dual-write path: resolves the
     * association type code and each SKU to their ids (skipping unresolved
     * SKUs and self-links), then delegates to the shared sync core.
     *
     * Unlike `syncType`/`syncTypeWithData`, this path never carries
     * per-link `additional_data` (ordinary product saves only know SKU
     * membership), so surviving rows have their existing `additional_data`
     * PRESERVED rather than overwritten to null — otherwise a plain product
     * save would silently wipe out custom-field values (e.g. quantity) the
     * association UI previously wrote for that link.
     *
     * Reuses the same SKU -> id resolution mechanism as the product
     * importer (`ProductRepository::findWhereIn('sku', ...)`, the same
     * query `SKUStorage` performs internally).
     */
    public function syncFromSkuList(int $productId, string $typeCode, array $skus): void
    {
        $associationType = $this->associationTypeRepository->findByCode($typeCode);

        if (! $associationType) {
            return;
        }

        $skus = array_values(array_unique(array_filter(
            $skus,
            fn ($sku) => is_string($sku) && $sku !== ''
        )));

        $resolvedProducts = empty($skus)
            ? collect()
            : $this->productRepository->findWhereIn('sku', $skus, ['id', 'sku']);

        $links = [];

        foreach ($resolvedProducts as $resolvedProduct) {
            $relatedProductId = (int) $resolvedProduct->id;

            if ($relatedProductId === $productId) {
                continue;
            }

            $links[] = [
                'related_product_id'  => $relatedProductId,
                'position'            => null,
                'additional_data'     => null,
            ];
        }

        $this->syncLinks($productId, $associationType->id, $links, preserveAdditionalData: true);
    }
}
