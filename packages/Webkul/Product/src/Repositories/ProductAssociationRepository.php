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
     */
    public function syncType(int $productId, int $associationTypeId, array $links): void
    {
        Event::dispatch('product_association.sync.before', [$productId, $associationTypeId, $links]);

        DB::transaction(function () use ($productId, $associationTypeId, $links) {
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
                    if (
                        $existingLink->position !== $position
                        || $existingLink->additional_data !== $additionalData
                    ) {
                        $existingLink->update([
                            'position'        => $position,
                            'additional_data' => $additionalData,
                        ]);
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
     * SKUs and self-links), then delegates to `syncType`.
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

        $this->syncType($productId, $associationType->id, $links);
    }
}
