<?php

namespace Webkul\DataTransfer\Helpers\Importers\ProductAssociation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;
use Webkul\Product\Validator\AssociationValidator;

/**
 * Row-per-link association import job.
 *
 * Each row is `sku,association_type,related_sku` plus one column per active
 * association type field code (e.g. `quantity`). Unlike the wide product
 * import (which replaces a whole association type's link set per product),
 * this job accumulates links across batched rows via the Task-1
 * `upsertLink`/`deleteLink` primitives, so multiple rows for the same
 * `(sku, association_type)` pair add up rather than overwrite each other.
 *
 * Field columns are validated/persisted as the `common` bucket only.
 * Locale-specific association fields (`value_per_locale = 1`) are not
 * addressable from this row-per-link format yet — a locale-qualified
 * column convention (e.g. `quantity[en_US]`) is a documented later
 * extension, not implemented here.
 */
class Importer extends AbstractImporter
{
    /**
     * A required column (sku/association_type/related_sku) was empty
     */
    public const ERROR_REQUIRED_FIELD_MISSING = 'required_field_missing';

    /**
     * `sku` and `related_sku` are the same product
     */
    public const ERROR_SELF_LINK = 'self_link_not_allowed';

    /**
     * `sku` does not resolve to an existing product
     */
    public const ERROR_SKU_NOT_FOUND = 'sku_not_found';

    /**
     * `related_sku` does not resolve to an existing product
     */
    public const ERROR_RELATED_SKU_NOT_FOUND = 'related_sku_not_found';

    /**
     * `association_type` does not resolve to an existing, active type
     */
    public const ERROR_TYPE_NOT_FOUND = 'association_type_not_found';

    /**
     * One of the row's custom field values failed `AssociationValidator`
     */
    public const ERROR_FIELD_VALIDATION = 'invalid_field_value';

    /**
     * Permanent entity columns accepted from CSV
     */
    protected array $permanentAttributes = ['sku', 'association_type', 'related_sku'];

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_REQUIRED_FIELD_MISSING => 'data_transfer::app.importers.product-associations.validation.errors.required-field-missing',
        self::ERROR_SELF_LINK              => 'data_transfer::app.importers.product-associations.validation.errors.self-link-not-allowed',
        self::ERROR_SKU_NOT_FOUND          => 'data_transfer::app.importers.product-associations.validation.errors.sku-not-found',
        self::ERROR_RELATED_SKU_NOT_FOUND  => 'data_transfer::app.importers.product-associations.validation.errors.related-sku-not-found',
        self::ERROR_TYPE_NOT_FOUND         => 'data_transfer::app.importers.product-associations.validation.errors.association-type-not-found',
        self::ERROR_FIELD_VALIDATION       => 'data_transfer::app.importers.product-associations.validation.errors.invalid-field-value',
    ];

    /**
     * Active association type fields keyed by association_type_id, cached
     * for the lifetime of this importer instance
     */
    protected array $typeFieldsCache = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected ProductAssociationRepository $productAssociationRepository,
        protected AssociationTypeFieldRepository $associationTypeFieldRepository,
        protected AssociationValidator $associationValidator,
        protected Storage $productAssociationStorage,
    ) {
        parent::__construct($importBatchRepository);

        $this->initValidColumnNames();
    }

    /**
     * Register error message templates
     */
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    /**
     * Build `$validColumnNames` dynamically: the 3 permanent columns plus
     * every active association type field code across all association
     * types (queried once).
     */
    protected function initValidColumnNames(): void
    {
        $fieldCodes = $this->associationTypeFieldRepository
            ->where(['status' => 1])
            ->pluck('code')
            ->unique()
            ->values()
            ->all();

        $this->validColumnNames = array_values(array_unique(array_merge(
            $this->permanentAttributes,
            $fieldCodes
        )));
    }

    /**
     * Validate data before import
     */
    public function validateData(): void
    {
        $this->productAssociationStorage->init();

        parent::validateData();
    }

    /**
     * Validates a single row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        $sku = trim((string) ($rowData['sku'] ?? ''));
        $relatedSku = trim((string) ($rowData['related_sku'] ?? ''));
        $typeCode = trim((string) ($rowData['association_type'] ?? ''));

        if ($sku === '') {
            $this->skipRow($rowNumber, self::ERROR_REQUIRED_FIELD_MISSING, 'sku');

            return false;
        }

        if ($relatedSku === '') {
            $this->skipRow($rowNumber, self::ERROR_REQUIRED_FIELD_MISSING, 'related_sku');

            return false;
        }

        if ($typeCode === '') {
            $this->skipRow($rowNumber, self::ERROR_REQUIRED_FIELD_MISSING, 'association_type');

            return false;
        }

        if ($sku === $relatedSku) {
            $this->skipRow($rowNumber, self::ERROR_SELF_LINK, $sku);

            return false;
        }

        $this->productAssociationStorage->loadProducts([$sku, $relatedSku]);

        if (! $this->productAssociationStorage->hasProduct($sku)) {
            $this->skipRow($rowNumber, self::ERROR_SKU_NOT_FOUND, $sku);

            return false;
        }

        if (! $this->productAssociationStorage->hasProduct($relatedSku)) {
            $this->skipRow($rowNumber, self::ERROR_RELATED_SKU_NOT_FOUND, $relatedSku);

            return false;
        }

        if (! $this->productAssociationStorage->hasActiveType($typeCode)) {
            $this->skipRow($rowNumber, self::ERROR_TYPE_NOT_FOUND, $typeCode);

            return false;
        }

        if ($this->import->action != Import::ACTION_DELETE) {
            $typeId = $this->productAssociationStorage->getTypeId($typeCode);
            $additionalData = $this->buildAdditionalData($rowData, $typeId);

            try {
                $this->associationValidator->validate($typeId, $additionalData);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $fieldMessages) {
                    $this->skipRow($rowNumber, self::ERROR_FIELD_VALIDATION, $field, current($fieldMessages));
                }

                return false;
            }
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteAssociationData($batch);
        } else {
            $this->saveAssociationData($batch);
        }

        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    /**
     * Append mode: upsert each row's link via the Task-1 single-row
     * primitive so it accumulates alongside links written by other
     * batches of the same import (and by any prior import/UI action),
     * instead of replacing the whole `(product, association_type)` set.
     */
    protected function saveAssociationData(JobTrackBatchContract $batch): bool
    {
        $this->loadBatchLookups($batch);

        foreach ($batch->data as $rowData) {
            [$productId, $relatedProductId, $typeId] = $this->resolveRowIds($rowData);

            if (! $productId || ! $relatedProductId || ! $typeId) {
                continue;
            }

            $additionalData = $this->buildAdditionalData($rowData, $typeId);

            $this->productAssociationRepository->upsertLink(
                $productId,
                $typeId,
                $relatedProductId,
                null,
                $additionalData
            );

            $this->createdItemsCount++;
        }

        return true;
    }

    /**
     * Delete mode: remove a single link identified by
     * `(sku, association_type, related_sku)`, leaving every other link of
     * that type (and product) untouched.
     */
    protected function deleteAssociationData(JobTrackBatchContract $batch): bool
    {
        $this->loadBatchLookups($batch);

        foreach ($batch->data as $rowData) {
            [$productId, $relatedProductId, $typeId] = $this->resolveRowIds($rowData);

            if (! $productId || ! $relatedProductId || ! $typeId) {
                continue;
            }

            $this->productAssociationRepository->deleteLink($productId, $typeId, $relatedProductId);

            $this->deletedItemsCount++;
        }

        return true;
    }

    /**
     * Pre-load the product/type lookups for every row in the batch
     */
    protected function loadBatchLookups(JobTrackBatchContract $batch): void
    {
        $skus = [];

        foreach ($batch->data as $rowData) {
            $skus[] = $rowData['sku'] ?? null;
            $skus[] = $rowData['related_sku'] ?? null;
        }

        $this->productAssociationStorage->loadProducts(array_filter($skus));
    }

    /**
     * Resolve a row's sku/related_sku/association_type columns to ids
     *
     * @return array{0: ?int, 1: ?int, 2: ?int} [productId, relatedProductId, typeId]
     */
    protected function resolveRowIds(array $rowData): array
    {
        $productId = $this->productAssociationStorage->getProductId((string) ($rowData['sku'] ?? ''));
        $relatedProductId = $this->productAssociationStorage->getProductId((string) ($rowData['related_sku'] ?? ''));
        $typeId = $this->productAssociationStorage->getTypeId((string) ($rowData['association_type'] ?? ''));

        return [$productId, $relatedProductId, $typeId];
    }

    /**
     * Build the `additional_data` payload from the row's field columns,
     * limited to the fields that belong to the given association type
     * (fields of other types present in the CSV header are ignored for
     * this row). Only the `common` bucket is populated — see class docblock.
     */
    protected function buildAdditionalData(array $rowData, int $typeId): array
    {
        $common = [];

        foreach ($this->getFieldsForType($typeId) as $field) {
            $code = $field->code;

            if (
                ! array_key_exists($code, $rowData)
                || $rowData[$code] === null
                || $rowData[$code] === ''
            ) {
                continue;
            }

            $common[$code] = $rowData[$code];
        }

        return ['common' => $common];
    }

    /**
     * Retrieve (and cache) the active custom fields for an association type
     */
    protected function getFieldsForType(int $typeId): Collection
    {
        if (! isset($this->typeFieldsCache[$typeId])) {
            $this->typeFieldsCache[$typeId] = $this->associationTypeFieldRepository
                ->where(['association_type_id' => $typeId, 'status' => 1])
                ->get();
        }

        return $this->typeFieldsCache[$typeId];
    }
}
