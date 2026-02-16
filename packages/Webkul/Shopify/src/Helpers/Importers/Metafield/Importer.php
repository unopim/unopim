<?php

namespace Webkul\Shopify\Helpers\Importers\Metafield;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;

class Importer extends AbstractImporter
{
    use ShopifyGraphqlRequest;

    public const BATCH_SIZE = 10;

    /**
     * cursor position
     */
    public $cursor = null;

    /**
     * locales storage
     */
    protected array $locales = [];

    /**
     * Shopify job Locale.
     *
     * @var mixed
     */
    protected $locale;

    protected array $attrStrore = [];

    protected $attributeType = [
        'single_line_text_field' => 'text',
        'json'                   => 'textarea',
        'number_integer'         => 'text',
        'multi_line_text_field'  => 'textarea',
    ];

    /**
     * Shopify credential.
     *
     * @var mixed
     */
    protected $credential;

    /**
     * Shopify credential as array for api request.
     *
     * @var mixed
     */
    protected $credentialArray;

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeRepository $attributeRepository,
        protected LocaleRepository $localeRepository,
        protected ShopifyCredentialRepository $shopifyRepository,
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
    }

    /**
     * Initialize locales
     */
    protected function initLocales(): void
    {
        $this->locales = $this->localeRepository->getActiveLocales()->pluck('code')->toArray();
    }

    /**
     * Initialize Filters
     */
    protected function initFilters(): void
    {
        $filters = $this->import->jobInstance->filters;

        $this->credential = $this->shopifyRepository->find($filters['credentials'] ?? null);

        $this->locale = $filters['locale'] ?? null;
    }

    /**
     * Import instance.
     *
     * @return \Webkul\DataTransfer\Helpers\Source
     */
    public function getSource()
    {
        $this->initFilters();
        if (! $this->credential?->active) {
            throw new \InvalidArgumentException('Invalid Credential: The credential is either disabled, incorrect, or does not exist');
        }
        $this->credentialArray = [
            'shopUrl'     => $this->credential?->shopUrl,
            'accessToken' => $this->credential?->accessToken,
            'apiVersion'  => $this->credential?->apiVersion,
        ];

        $productMetafieldDefinition = $this->metaFieldAttrByCursor();
        $productVariantMetaField = $this->metaFieldProductVariant();

        $requestData = $this->credential?->extras;
        $requestData['productMetafield'] = array_combine(array_column($productMetafieldDefinition, 'code'), array_column($productMetafieldDefinition, 'namespace'));
        $requestData['productVariantMetafield'] = array_combine(array_column($productVariantMetaField, 'code'), array_column($productVariantMetaField, 'namespace'));
        $filters = $this->import->jobInstance->filters;

        $this->shopifyRepository->update(['extras' => $requestData], $filters['credentials']);

        $mergeMetafield = array_merge($productVariantMetaField, $productMetafieldDefinition);

        $metafieldProductAttr = new \ArrayIterator($mergeMetafield);

        return $metafieldProductAttr;
    }

    /**
     * ProductVariant Attr
     */
    public function metaFieldProductVariant(): array
    {
        $cursor = null;
        $allAttribute = [];
        $formattedOption = [];
        do {
            $variables = [];
            $mutationType = 'metafieldDefinitionsProductVariantType';
            $variables = [
                'first'       => 20,
                'after'       => $cursor,
            ];
            $graphResponse = $this->requestGraphQlApiAction($mutationType, $this->credentialArray, $variables);

            $metafieldAttribute = ! empty($graphResponse['body']['data']['metafieldDefinitions']['edges'])
                ? $graphResponse['body']['data']['metafieldDefinitions']['edges']
                : [];

            $formattedOption = $this->formatedAttribute($metafieldAttribute);

            $allAttribute = array_merge($allAttribute, $formattedOption);
            $lastCursor = ! empty($metafieldAttribute) ? end($metafieldAttribute)['cursor'] : null;

            if ($cursor === $lastCursor || empty($lastCursor)) {
                break;
            }

            $cursor = $lastCursor;

        } while (! empty($metafieldAttribute));

        return $allAttribute;
    }

    /**
     * Attribute Getting by cursor
     */
    public function metaFieldAttrByCursor(): array
    {
        $cursor = null;
        $allAttribute = [];
        $formattedOption = [];
        do {
            $variables = [];
            $mutationType = 'metafieldDefinitionsProductType';
            $variables = [
                'first'       => 20,
                'after'       => $cursor,
            ];
            $graphResponse = $this->requestGraphQlApiAction($mutationType, $this->credentialArray, $variables);

            $metafieldAttribute = ! empty($graphResponse['body']['data']['metafieldDefinitions']['edges'])
                ? $graphResponse['body']['data']['metafieldDefinitions']['edges']
                : [];
            $formattedOption = $this->formatedAttribute($metafieldAttribute);

            $allAttribute = array_merge($allAttribute, $formattedOption);
            $lastCursor = ! empty($metafieldAttribute) ? end($metafieldAttribute)['cursor'] : null;

            if ($cursor === $lastCursor || empty($lastCursor)) {
                break;
            }

            $cursor = $lastCursor;

        } while (! empty($metafieldAttribute));

        return $allAttribute;
    }

    /**
     * Formating Attribute and attriute Option
     */
    public function formatedAttribute(array $attributes): array
    {
        $attributesArray = [];

        foreach ($attributes as $attribute) {
            $metafieldType = $attribute['node']['type']['name'];
            if (! isset($this->attributeType[$metafieldType])) {
                continue;
            }

            $attributeFormate = [
                'code'        => $attribute['node']['key'],
                'type'        => $this->attributeType[$metafieldType],
                'namespace'   => $attribute['node']['namespace'],
                $this->locale => [
                    'name' => $attribute['node']['name'],
                ],
            ];

            if ($metafieldType == 'number_integer') {
                $attributeFormate['validation'] = 'number';
            }

            $attributesArray[] = $attributeFormate;
        }

        return $attributesArray;
    }

    /**
     * Validate data for saving attribute
     */
    public function validateData(): void
    {
        $this->saveValidatedBatches();
    }

    /**
     * Save validated batches
     */
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        $batchRows = [];

        $source->rewind();
        /**
         * Clean previous saved batches
         */
        $this->importBatchRepository->deleteWhere([
            'job_track_id' => $this->import->id,
        ]);

        while (
            $source->valid()
            || count($batchRows)
        ) {
            if (
                count($batchRows) == self::BATCH_SIZE
                || ! $source->valid()
            ) {
                $this->importBatchRepository->create([
                    'job_track_id' => $this->import->id,
                    'data'         => $batchRows,
                ]);

                $batchRows = [];
            }

            if ($source->valid()) {
                $rowData = $source->current();

                if ($this->validateRow($rowData, 1)) {
                    $batchRows[] = $this->prepareRowForDb($rowData);
                }

                $this->processedRowsCount++;

                $source->next();
            }
        }

        return $this;
    }

    /**
     * Start the import process for Attribute Import
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->saveAttributeData($batch);

        return true;
    }

    /**
     * Create or update attribute and attribute Options
     */
    public function saveAttributeData(JobTrackBatchContract $batch): bool
    {
        $this->initFilters();
        $attributes = [];

        foreach ($batch->data as $rowData) {
            $attributeModel = $this->attributeRepository->findOneByField('code', strtolower($rowData['code']));
            if ($attributeModel) {
                $this->updatedItemsCount++;
            } else {
                unset($rowData['namespace']);
                $newlyAttrCreated = $this->attributeRepository->create($rowData);
                $this->createdItemsCount++;
            }
        }

        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        return true;
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        return true;
    }
}
