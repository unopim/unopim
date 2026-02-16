<?php

namespace Webkul\Shopify\Helpers\Importers\Family;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeFamilyGroupMappingRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyExportMappingRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;

class Importer extends AbstractImporter
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;

    public const BATCH_SIZE = 10;

    public const UNOPIM_ENTITY_NAME = 'familyCount';

    /**
     * cursor position
     */
    public $cursor = null;

    /**
     * locales storage
     */
    protected array $locales = [];

    /**
     * job locale
     */
    private $locale;

    protected array $familyCode = [];

    /**
     * Shopify credential.
     *
     * @var mixed
     */
    protected $credential;

    protected $defintiionMapping;

    /**
     * Shopify credential as array for api request.
     *
     * @var mixed
     */
    protected $credentialArray;

    protected $importMapping;

    protected $attributeGroupId = 0;

    protected $FamilyCount;

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryRepository $categoryRepository,
        protected Storage $categoryStorage,
        protected AttributeRepository $attributeRepository,
        protected LocaleRepository $localeRepository,
        protected ShopifyCredentialRepository $shopifyRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ShopifyExportMappingRepository $shopifyExportmapping,
        protected AttributeFamilyGroupMappingRepository $attributeFamilyGroupMappingRepository,
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
        $this->importMapping = $this->shopifyExportmapping->find(3);
    }

    /**
     * Initialize Filters
     */
    protected function initFilters(): void
    {
        $filters = $this->import->jobInstance->filters;

        $this->credential = $this->shopifyRepository->find($filters['credentials'] ?? null);

        $this->defintiionMapping = array_merge(array_keys($this->credential?->extras['productMetafield'] ?? []), array_keys($this->credential?->extras['productVariantMetafield'] ?? []));

        $this->locale = $filters['locale'] ?? null;

        $this->attributeGroupId = $filters['attributegroupid'] ?? null;

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

        $attributeAndOption = new \ArrayIterator($this->productOptionByCursor());

        return $attributeAndOption;
    }

    /**
     * Family Getting by cursor
     */
    public function productOptionByCursor(): array
    {
        $cursor = null;
        $allFamily = [];
        $formattedOption = [];
        $optionWithVariant = [];
        do {
            $variables = [];
            $mutationType = 'productGettingOptions';
            if ($cursor) {
                $variables = [
                    'first'       => 50,
                    'afterCursor' => $cursor,
                ];
                $mutationType = 'productOptionByCursor';
            }

            $graphResponse = $this->requestGraphQlApiAction($mutationType, $this->credentialArray, $variables);

            $graphqlOption = ! empty($graphResponse['body']['data']['products']['edges'])
                ? $graphResponse['body']['data']['products']['edges']
                : [];

            $formattedOption = $this->formatedAttributeAndOption($graphqlOption, $optionWithVariant);
            $optionWithVariant = array_unique($optionWithVariant);

            $allFamily = array_merge($allFamily, $formattedOption);
            $lastCursor = ! empty($graphqlOption) ? end($graphqlOption)['cursor'] : null;

            if ($cursor === $lastCursor || empty($lastCursor)) {
                break;
            }
            $cursor = $lastCursor;

        } while (! empty($graphqlOption));
        $simpleproductFamily = $this->familymodifyforsimpleProduct($allFamily, $optionWithVariant);

        return $allFamily;
    }

    public function familymodifyforsimpleProduct($family, $optionWithVariant)
    {
        $importMapping = $this->importMapping->mapping ? $this->importMapping->mapping['shopify_connector_settings'] : [];
        $imagesAttr = $this->importMapping->mapping['mediaMapping'] ?? null;

        $allImageAttr = [];
        if ($imagesAttr) {
            $allImageAttr = explode(',', $imagesAttr['mediaAttributes']);
        }

        $simpleProductFamilyId = $importMapping['family_variant'] ?? null;
        unset($importMapping['family_variant']);
        $metaFieldAllAttr = array_merge($optionWithVariant, array_unique($this->defintiionMapping), array_values($importMapping), $allImageAttr);
        $metaFieldAllAttr[] = 'sku';
        $metaFieldAttrIds = $this->attributeRepository->whereIn('code', $metaFieldAllAttr)->pluck('id')->toArray();
        if ($simpleProductFamilyId) {
            $familyModel = $this->attributeFamilyRepository->find($simpleProductFamilyId);
            if (! $familyModel) {
                throw new \Exception('Product family mapping not found.');
            }
            $familyModel = $familyModel->first();

            $allIds = $this->attributeFamilyGroupMappingRepository->whereIn('attribute_family_id', [$simpleProductFamilyId])->pluck('id')->toArray();

            $groupMappingId = $this->attributeFamilyGroupMappingRepository->findWhere([
                'attribute_group_id'  => $this->attributeGroupId,
                'attribute_family_id' => $simpleProductFamilyId,
            ])->first()?->id;

            $allIdss = [];

            foreach ($allIds as $groupId) {

                $attributeIdss = DB::table('attribute_group_mappings')
                    ->whereIn('attribute_family_group_id', [$groupId])
                    ->pluck('attribute_id')->toArray();
                $allIdss = array_merge($allIdss, $attributeIdss);

            }

            $notInMetafields = array_diff($metaFieldAttrIds, $allIdss);
            if (! empty($notInMetafields)) {
                if (! $groupMappingId) {
                    $groupMappingId = $this->attributeFamilyGroupMappingRepository->insertGetId([
                        'attribute_group_id'  => $this->attributeGroupId,
                        'attribute_family_id' => $simpleProductFamilyId,
                    ]);
                }
                $data = array_map(function ($notInMetafield) use ($groupMappingId) {
                    return [
                        'attribute_id'              => $notInMetafield,
                        'attribute_family_group_id' => $groupMappingId,
                    ];
                }, $notInMetafields);

                DB::table('attribute_group_mappings')->insertOrIgnore($data);
            }
        }
    }

    /**
     * Formated family of attributes
     */
    public function formatedAttributeAndOption(array $options, &$optionWithVariant): array
    {
        $family = [];
        $family_codes = [];
        foreach ($options as $option) {
            $optionName = array_column($option['node']['options'], 'name');
            $optionName = array_map(function ($value) {
                return trim(preg_replace('/[^A-Za-z0-9]+/', '_', $value), '_');
            }, $optionName);

            $optionValues = array_column($option['node']['options'], 'values');
            $optionValues = array_merge(...array_map('array_values', $optionValues)); // Flatten the array
            if (in_array('Title', $optionName) && in_array('Default Title', $optionValues)) {
                continue;
            }
            $lowercaseArray = array_map('strtolower', $optionName);
            $optionWithVariant = array_merge($lowercaseArray, $optionWithVariant);
            $importMappingAttr = $this->importMapping->mapping ? $this->importMapping->mapping['shopify_connector_settings'] : [];

            $imageMappingAttr = $importMappingAttr['images'] ?? '';

            unset($importMappingAttr['images']);
            unset($importMappingAttr['family_variant']);

            $allAttrForFamily = array_merge(array_unique(array_values($importMappingAttr)), $lowercaseArray, explode(',', $imageMappingAttr), $this->defintiionMapping);

            $attrId = [];

            $family_code = preg_replace(['/,/', '/[^a-zA-Z0-9_]/'], ['_', ''], json_encode($lowercaseArray));
            $allAttrForFamily[] = 'sku';
            $allAttrForFamily[] = 'status';
            $allAttrForFamily = array_filter(array_unique($allAttrForFamily));
            foreach ($allAttrForFamily as $key => $attrCode) {
                $attributeModel = $this->attributeRepository->findOneByField('code', $attrCode);
                if (! $attributeModel) {
                    continue;
                }
                $attrId[] = [
                    'id'       => (string) $attributeModel?->id,
                    'position' => (string) $key,
                ];
            }
            $family[] = [
                'code'        => $family_code,
                $this->locale => [
                    'name' => $family_code,
                ],
                'attribute_groups' => [
                    $this->attributeGroupId => [
                        'position'          => 1,
                        'custom_attributes' => $attrId,
                    ],
                ],
            ];

            $family_codes[] = $family_code;
        }

        return $family;
    }

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
     * Start the import process For Family Import Data
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->saveFamilyData($batch);

        return true;
    }

    /**
     * Save the family
     */
    public function saveFamilyData(JobTrackBatchContract $batch): bool
    {
        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => 1,
                'updated' => 1,
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
