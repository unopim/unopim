<?php

namespace Webkul\DataTransfer\Helpers\Exporters\ProductAssociation;

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;

/**
 * Row-per-link association export job — the export counterpart to the
 * Task-3 `ProductAssociation\Importer`.
 *
 * Each exported row is `sku,association_type,related_sku` plus one column
 * per active, NON-locale (`value_per_locale = 0`) association type field
 * code, unioned across every association type (a "sparse" row: only the
 * columns relevant to that link's type are populated, every other field
 * column is empty). Column names and the "common" bucket value source
 * intentionally mirror the importer exactly, so a file exported here
 * re-imports cleanly:
 *
 * - `getResults()` reads `product_associations` joined once to `products`
 *   (twice, aliased, for the source/related SKU) and to `association_types`
 *   (for the type code) — a single query, no per-row lookups.
 * - `prepareAssociations()` turns each joined row into the final flat
 *   array, reading each field's value from `additional_data['common']`.
 *
 * Locale-specific fields (`value_per_locale = 1`) are never exported: the
 * importer only ever persists the `common` bucket (see the importer's
 * docblock), so there is nothing meaningful to export for them here either.
 */
class Exporter extends AbstractExporter
{
    /**
     * Permanent entity columns always present in the export
     */
    protected array $permanentAttributes = ['sku', 'association_type', 'related_sku'];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected AssociationTypeFieldRepository $associationTypeFieldRepository,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the file buffer for the export process.
     *
     * @return void
     */
    public function initilize()
    {
        $this->initializeFileBuffer();
    }

    /**
     * Start the export process.
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $associations = $this->prepareAssociations($batch);

        $this->exportBuffer->write($associations);

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Single join query across `product_associations`, `products` (twice,
     * aliased, for the link's source/related SKU) and `association_types`
     * (for the type code) — avoids a per-row lookup for every link.
     */
    protected function getResults()
    {
        return $this->source
            ->getModel()
            ->newQuery()
            ->select([
                'product_associations.id as id',
                'product_associations.additional_data as additional_data',
                'wk_pa_source_products.sku as sku',
                'association_types.code as association_type',
                'wk_pa_related_products.sku as related_sku',
            ])
            ->join('products as wk_pa_source_products', 'wk_pa_source_products.id', '=', 'product_associations.product_id')
            ->join('products as wk_pa_related_products', 'wk_pa_related_products.id', '=', 'product_associations.related_product_id')
            ->join('association_types', 'association_types.id', '=', 'product_associations.association_type_id')
            ->orderBy('product_associations.id')
            ->get()
            ->getIterator();
    }

    /**
     * Prepare association link rows from the current batch.
     *
     * Every row gets every union'd field-code key (sparse per row: only the
     * fields belonging to that row's association type are ever non-null),
     * so every row shares the exact same set of column keys — required
     * since the flat file buffer derives the header from the first row's
     * keys alone.
     */
    public function prepareAssociations(JobTrackBatchContract $batch): array
    {
        $fieldCodes = $this->getNonLocaleFieldCodes();
        $associations = [];

        foreach ($batch->data as $rowData) {
            $additionalData = $rowData['additional_data'] ?? [];

            if (is_string($additionalData)) {
                $additionalData = json_decode($additionalData, true) ?? [];
            }

            $common = $additionalData['common'] ?? [];

            $row = [];

            foreach ($this->permanentAttributes as $attribute) {
                $row[$attribute] = $rowData[$attribute] ?? null;
            }

            foreach ($fieldCodes as $code) {
                $row[$code] = $common[$code] ?? null;
            }

            $associations[] = $row;

            $this->createdItemsCount++;
        }

        return $associations;
    }

    /**
     * Union of every active, non-locale association type field code across
     * every association type. Locale-specific (`value_per_locale = 1`)
     * fields are excluded — the importer never persists them (see the
     * importer's docblock), so there is nothing to export for them.
     */
    protected function getNonLocaleFieldCodes(): array
    {
        return $this->associationTypeFieldRepository
            ->where(['status' => 1, 'value_per_locale' => 0])
            ->pluck('code')
            ->unique()
            ->values()
            ->all();
    }
}
