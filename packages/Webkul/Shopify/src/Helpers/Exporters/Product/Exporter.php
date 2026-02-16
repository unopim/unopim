<?php

namespace Webkul\Shopify\Helpers\Exporters\Product;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeFamilyGroupMappingRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Shopify\Exceptions\InvalidCredential;
use Webkul\Shopify\Exceptions\InvalidLocale;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyExportMappingRepository;
use Webkul\Shopify\Repositories\ShopifyMappingRepository;
use Webkul\Shopify\Repositories\ShopifyMetaFieldRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;
use Webkul\Shopify\Traits\TranslationTrait;

class Exporter extends AbstractExporter
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;
    use TranslationTrait;

    public const UNOPIM_ENTITY_NAME = 'product';

    public const NOT_EXIST_PRODUCT = 'Product does not exist';

    public const VARIANT_CREATE = 'productVariantsBulkCreate';

    public const VARIANT_UPDATE = 'productVariantsBulkUpdate';

    public const NOT_EXIST_PRODUCT_VARIANT = 'Product variant does not exist';

    protected $productIndexes = ['title', 'handle', 'vendor', 'descriptionHtml', 'productType'];

    protected $seoFileds = ['metafields_global_title_tag', 'metafields_global_description_tag'];

    protected $variantIndexes = ['price', 'weight', 'cost', 'compareAtPrice', 'barcode', 'taxable', 'inventoryPolicy', 'sku', 'inventoryTracked', 'inventoryQuantity'];

    protected $imageMineType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];

    protected $credential;

    protected $imageData = [];

    public const BATCH_SIZE = 50;

    /**
     * @var array
     */
    protected $childImageAttr = [];

    protected $removeImgAttr = [];

    protected $parentImageAttr = [];

    protected $variantMetafieldAttrCode = [];

    protected bool $exportsFile = false;

    /**
     * @var array
     */
    protected $currencies = [];

    /**
     * @var array
     */
    protected $attributes = [];

    protected $currency;

    protected $jobChannel;

    protected $settingMapping;

    protected $shopifyDefaultLocale;

    protected $imageAttributes;

    protected $productMetaFieldMapping = [];

    protected $variantMetaFieldMapping = [];

    protected $updateMedia = [];

    protected $metaFieldAttributeCode = [];

    protected $definitionMapping = [];

    protected $productId = [];

    protected $credentialAsArray = [];

    protected $exportMapping;

    protected $publicationId = [];

    protected $locationId;

    protected $productOptions;

    protected $attributesAll = [];

    protected $assetAttr = [];

    public $seprators = [
        'colon' => ': ',
        'dash'  => '- ',
        'space' => ' ',
    ];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected ChannelRepository $channelRepository,
        protected AttributeRepository $attributeRepository,
        protected ShopifyCredentialRepository $shopifyRepository,
        protected ShopifyMappingRepository $shopifyMappingRepository,
        protected ShopifyExportMappingRepository $shopifyExportmapping,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeFamilyGroupMappingRepository $attributeFamilyGroupMappingRepository,
        protected ShopifyGraphQLDataFormatter $shopifyGraphQLDataFormatter,
        protected ShopifyMetaFieldRepository $shopifyMetaFieldRepository,
        protected ?AssetRepository $assetRepository = null,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the channels and locales for the export process.
     *
     * @return void
     */
    public function initilize()
    {
        $this->initCredential();
        $this->attributesAll = $this->attributeRepository->all()->keyBy('code');

        $this->initPublications();

        $this->initDefaultLocale();

        $this->shopifyGraphQLDataFormatter->setInitialData($this->locationId, $this->currency, $this->settingMapping, $this->attributesAll);
    }

    /**
     * Initialize credentials data from filters
     */
    protected function initCredential(): void
    {
        $filters = $this->getFilters();

        $this->currency = $filters['currency'];

        $this->jobChannel = $filters['channel'];

        $this->credential = $this->shopifyRepository->find($filters['credentials']);
        $this->definitionMapping = $this->credential?->extras;

        $mappings = $this->shopifyExportmapping->findMany([1, 2]);
        $this->exportMapping = $mappings->first();
        $this->productMetaFieldMapping = $this->shopifyMetaFieldRepository->where('ownerType', 'PRODUCT')->get()->toArray();
        $this->variantMetaFieldMapping = $this->shopifyMetaFieldRepository->where('ownerType', 'PRODUCTVARIANT')->get()->toArray();

        $this->settingMapping = $mappings->last();

        if (! $this->credential?->active) {
            $this->jobLogger->warning(trans('shopify::app.shopify.export.errors.invalid-credential'));

            $this->export->state = ExportHelper::STATE_FAILED;

            $this->export->errors = [trans('shopify::app.shopify.export.errors.invalid-credential')];
            $this->export->save();

            throw new InvalidCredential;
        }

        $this->credentialAsArray = [
            'shopUrl'     => $this->credential?->shopUrl,
            'accessToken' => $this->credential?->accessToken,
            'apiVersion'  => $this->credential?->apiVersion,
        ];
    }

    /**
     * Initialize publication from credentials data
     */
    protected function initPublications(): void
    {
        if (empty($this->credential->extras['salesChannel'])) {
            return;
        }

        $salesChannel = explode(',', $this->credential->extras['salesChannel']);

        $this->locationId = $this->credential->extras['locations'] ?? null;

        foreach ($salesChannel as $value) {
            $this->publicationId[] = [
                'publicationId' => $value,
            ];
        }
    }

    /**
     * Initialize default locale from credentials data
     */
    protected function initDefaultLocale(): void
    {
        if ($this->credential->storeLocales) {
            $defaultLanguage = array_values(array_filter($this->credential->storeLocales, function ($language) {
                return isset($language['defaultlocale']) && $language['defaultlocale'] === true;
            }))[0] ?? null;

            $this->shopifyDefaultLocale = $this->credential->storelocaleMapping[$defaultLanguage['locale']] ?? null;
        }

        if (empty($this->shopifyDefaultLocale)) {
            $this->export->state = ExportHelper::STATE_FAILED;

            $this->export->errors = [trans('shopify::app.shopify.export.errors.invalid-locale')];

            $this->export->save();

            throw new InvalidLocale;
        }
    }

    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('shopify.product.export.before', $batch);

        $this->initilize();
        $products = $this->prepareProductsForShopify($batch, $filePath);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, ExportHelper::STATE_PROCESSED);

        Event::dispatch('shopify.product.export.after', $batch);

        return true;
    }

    protected function getResults()
    {
        $filters = $this->getFilters();

        $skus = null;

        $query = $this->source->select('sku');

        if (isset($filters['productfilter']) && ! empty($filters['productfilter'])) {
            $skus = explode(',', $filters['productfilter']);
            $skus = array_map('trim', $skus);

            $query->whereIn('sku', $skus);
        }

        return $query->get()?->getIterator();
    }

    public function prepareProductsForShopify(JobTrackBatchContract $batch, mixed $filePath)
    {
        $skus = array_column($batch->data, 'sku');
        $allProducts = DB::table('products')
            ->leftJoin('attribute_families as aft', 'products.attribute_family_id', '=', 'aft.id')
            ->leftJoin('products as parent_products', 'products.parent_id', '=', 'parent_products.id')

            ->leftJoin('product_super_attributes as psa', function ($join) {
                $join->on('parent_products.id', '=', 'psa.product_id')
                    ->orOn('products.id', '=', 'psa.product_id');
            })
            ->leftJoin('attributes as attr', 'psa.attribute_id', '=', 'attr.id')
            ->leftJoin('attribute_translations as attr_trans', 'attr.id', '=', 'attr_trans.attribute_id')
            ->select(
                'products.id',
                'products.sku',
                'products.status',
                'products.type',
                'products.values',
                'products.attribute_family_id',
                'products.additional',
                'products.created_at',
                'products.updated_at',
                'aft.code as attribute_family_code',

                // Parent Product Data
                'parent_products.id as parent_id',
                'parent_products.sku as parent_sku',
                'parent_products.type as parent_type',
                'parent_products.status as parent_status',
                'parent_products.values as parent_values',
                'parent_products.attribute_family_id as parent_attribute_family_id',

                // Fetch super attributes, ensuring they are retrieved from the parent
                DB::raw(
                    \Webkul\Core\Helpers\Database\GrammarQueryManager::getDriver() === 'pgsql'
                        ? "COALESCE(STRING_AGG(DISTINCT attr.code, ',' ORDER BY attr.code ASC), '') as super_attributes"
                        : "COALESCE(GROUP_CONCAT(DISTINCT attr.code ORDER BY attr.code ASC SEPARATOR ','), '') as super_attributes"
                )
            )
            ->where(function ($query) use ($skus) {
                $query->whereIn('products.sku', $skus)
                    ->orWhereIn('parent_products.sku', $skus);
            })
            ->where('products.type', '!=', 'configurable')
            ->groupBy('products.id')
            ->get()
            ->toArray();

        foreach ($allProducts as $product) {
            $parent = $product?->parent_values ?? null;
            $superAttrCode = explode(',', $product?->super_attributes);
            $superAttr = [];
            foreach ($superAttrCode as $attributeCode) {
                $attr = $this->attributesAll[$attributeCode] ?? null;
                if ($attr) {
                    $superAttr[] = [
                        'id'                => $attr?->id,
                        'code'              => $attr?->code,
                        'name'              => $attr?->name,
                        'type'              => $attr?->type,
                        'is_unique'         => $attr?->is_unique,
                        'is_required'       => $attr?->is_required,
                        'default_value'     => $attr?->default_value,
                        'regex_pattern'     => $attr?->regex_pattern,
                        'value_per_locale'  => $attr?->value_per_locale,
                        'value_per_channel' => $attr?->value_per_channel,
                        'usable_in_grid'    => $attr?->value_per_channel,
                        'translations'      => $attr->translations->toArray(),
                    ];
                }
            }

            if ($parent) {
                $parent = [
                    'id'                  => $product->parent_id,
                    'sku'                 => $product->parent_sku,
                    'type'                => $product->parent_type,
                    'status'              => $product->parent_status,
                    'values'              => json_decode($product->parent_values, true),
                    'attribute_family_id' => $product->parent_attribute_family_id,
                    'super_attributes'    => $superAttr,
                ];
            }
            $rowData = [
                'id'                  => $product->id,
                'sku'                 => $product->sku,
                'type'                => $product->type,
                'parent'              => $parent,
                'status'              => $product->status,
                'values'              => json_decode($product->values, true),
                'parent_id'           => $product->parent_id,
                'attribute_family_id' => $product->attribute_family_id,
                'additional'          => json_decode($product->additional, true),
                'super_attributes'    => [],
            ];
            $productResult = $this->processProductData($rowData);
            $this->createdItemsCount++;
            if (! $productResult) {
                continue;
            }
        }
    }

    public function processProductData(array $rowData): ?bool
    {
        $finalCategories = [];

        $this->imageData = [];

        $parentData = [];

        $parentMapping = [];

        $parentMergedFields = [];

        $skipParent = false;

        $optionsGetting = [];

        $optionValuesTranslation = [];

        $finalOption = [];

        $productOptionValues = [];

        $variableOption = [];

        $rowData['code'] = $rowData['sku'];

        $mapping = $this->checkMappingInDb($rowData) ?? null;

        $mergedFields = $this->gettingAllAttrValue($rowData);

        $this->getCategoriesByCode($rowData['values']['categories'] ?? [], $finalCategories);

        if (! empty($rowData['parent'])) {
            $parentMapping = $this->checkMappingInDb(['code' => $rowData['parent']['sku']]) ?? null;

            $skipParent = $parentMapping ? $this->export->id == $parentMapping[0]['jobInstanceId'] : false;

            $parentData = $rowData['parent'];

            $parentMergedFields = $this->gettingAllAttrValue($parentData);

            unset($rowData['parent']);

            $this->getCategoriesByCode($parentData['values']['categories'] ?? [], $finalCategories);

            [$variableOption, $productOptionValues, $finalOption, $optionValuesTranslation] = $this->processSuperAttributes($parentData['super_attributes'], $this->shopifyDefaultLocale, $mergedFields, $parentMapping, $mapping);
        }

        $formattedGraphqlData = $this->formatGraphqlData($mergedFields, $parentMergedFields, $finalCategories, $finalOption, $parentMapping);
        $mediaMappings = $this->exportMapping->mapping['mediaMapping'] ?? [];

        $imageData = [];
        if (! empty($mediaMappings) && $mediaMappings['mediaType'] === 'image') {
            $imageData = $this->formatImageDataForGraphqlImage($mergedFields, $mediaMappings, $parentMergedFields ?? []);
        }

        if (! empty($mediaMappings) && $mediaMappings['mediaType'] === 'gallery') {
            $imageData = $this->formatGalleryDataForGraphqlImage($mergedFields, $mediaMappings, $parentMergedFields ?? [], $skipParent);
        }
        if (! empty($imageData)) {
            $this->imageData = array_merge($imageData[$parentData['sku'] ?? ''] ?? [], $imageData[$rowData['sku']] ?? []);
        }

        $variantData = $formattedGraphqlData['variant'];
        unset($formattedGraphqlData['variant']);

        if (empty($mapping) && ! empty($parentMapping)) {
            $resultVariant = $this->processVariantCreationResult($formattedGraphqlData, $variantData, $imageData, $rowData, $productOptionValues, $parentMapping);
            if (! $resultVariant) {
                return null;
            }

            ['variantId' => $variantId, 'optionsGetting' => $optionsGetting, 'productId' => $productId] = $resultVariant;
        } else {
            if (empty($mapping)) {
                $createResult = $this->createShopifyProduct(
                    $formattedGraphqlData,
                    $parentData,
                    $rowData,
                    $variantData,
                    $imageData,
                    $productOptionValues
                );

                if (! $createResult) {
                    return null;
                }

                ['variantId' => $variantId, 'optionsGetting' => $optionsGetting, 'productId' => $productId] = $createResult;
            } else {
                $variantData = $variantData + $productOptionValues;
                $productId = ! empty($parentMapping) ? $parentMapping[0]['externalId'] : $mapping[0]['externalId'];
                ['variantId' => $variantId, 'optionsGetting' => $optionsGetting] = $this->processProductUpdate(
                    $skipParent,
                    $formattedGraphqlData,
                    $productId,
                    $parentMapping,
                    $mapping,
                    $parentData,
                    $rowData,
                    $imageData,
                    $variantData,
                    $variableOption,
                    $mediaMappings,
                    $finalOption,
                );
            }

            $this->handleProductProcessingForTranslation(
                $productId,
                $parentMergedFields,
                $mergedFields,
                $parentData,
                $rowData,
                $formattedGraphqlData
            );
        }

        $this->handleChildProductTranslation(
            $parentData,
            $mergedFields,
            $skipParent,
            $optionsGetting,
            $optionValuesTranslation,
            $productId,
            $variantId,
            $rowData
        );

        return true;
    }

    /**
     * Creates a Shopify product using the GraphQL API, either as a standalone product or a variant of an existing product.
     */
    private function createShopifyProduct(
        array $formattedGraphqlData,
        array $parentData,
        array $rowData,
        array $variantData,
        array $imageData,
        array $productOptionValues
    ): ?array {
        if (! empty($parentData)) {
            $variantData = $productOptionValues + $variantData;
            if (! empty($formattedGraphqlData['metafields'])) {
                $variantData['metafields'] = $formattedGraphqlData['metafields'];
            }
        }

        $result = $this->apiRequestShopifyProduct($formattedGraphqlData, $this->credentialAsArray);

        if (! $this->checkNotExistError($result)) {
            return null;
        }

        $productCreateErr = $result['body']['data']['productCreate']['userErrors'] ?? [];
        if (! empty($productCreateErr)) {
            if (! empty($formattedGraphqlData['parentMetaFields'])) {
                $this->prependAttributeCodesToErrors($productCreateErr, $formattedGraphqlData['parentMetaFields']);
            }
            $this->logWarning($productCreateErr, $parentData['sku'] ?? $rowData['sku']);
            $this->skippedItemsCount++;

            return null;
        }

        $productDataByApi = $result['body']['data']['productCreate']['product'];

        $productId = $productDataByApi['id'];

        $imageIds = $productDataByApi['media']['nodes'];

        $variantMediaId = reset($imageIds)['id'] ?? null;
        if (! empty($this->publicationId)) {
            $existingPublicationId = $productDataByApi['resourcePublications']['edges'] ?? [];
            $this->updateSalesChannelPublishing($productId, $existingPublicationId, $this->publicationId, $this->credentialAsArray);
        }
        if (! empty($parentData) && $variantMediaId) {
            $variantData['mediaId'] = $variantMediaId;
        }
        $this->parentMapping($parentData['sku'] ?? $rowData['sku'], $productId, $this->export->id);

        $this->imageIdMapping($imageIds, $imageData, $rowData, $parentData ?? [], $productId);

        $finalVariantData = [
            'productId'     => $productId,
            'strategy'      => 'REMOVE_STANDALONE_VARIANT',
            'variantsInput' => [$variantData],
        ];

        $result = $this->apiRequestShopifyDefaultVariantCreate($finalVariantData, $this->credentialAsArray);

        if (! $this->checkNotExistError($result)) {
            return null;
        }

        $variantErrorResult = $result['body']['data'][self::VARIANT_CREATE]['userErrors'] ?? [];
        if (! empty($variantErrorResult)) {
            $errors = array_column($variantErrorResult, 'message');
            if (! empty($variantData['metafields'])) {
                $this->variantMetafieldAttributeCodeError($errors, $variantErrorResult, $variantData['metafields']);
            }
            $this->logWarning($errors, $rowData['sku']);
            $this->skippedItemsCount++;

            return null;
        }

        $variantId = $result['body']['data'][self::VARIANT_CREATE]['productVariants'][0]['id'];

        $productOption = $result['body']['data'][self::VARIANT_CREATE]['product']['options'];
        if (! empty($parentData)) {
            $this->parentMapping($rowData['sku'], $variantId, $this->export->id, $productId);
        }

        return [
            'variantId'      => $variantId,
            'optionsGetting' => $productOption,
            'productId'      => $productId,
        ];
    }

    /**
     * Update Sales Channel Publishing
     *
     * */
    public function updateSalesChannelPublishing(string $productId, array $existingPublicationId, array $publicationsIds, array $credential): void
    {
        $existingIds = array_map(fn ($item) => $item['node']['publication']['id'], $existingPublicationId);
        $newIds = array_column($publicationsIds, 'publicationId');
        sort($existingIds);
        sort($newIds);
        if ($existingIds !== $newIds) {
            $productPublishFormate = [
                'id'                  => $productId,
                'productPublications' => $publicationsIds,
            ];
            $this->requestGraphQlApiAction('productPublish', $credential, ['input' => $productPublishFormate]);
            $removePublication = array_values(array_diff($existingIds, $newIds));
            if (! empty($removePublication)) {
                $removePublicationIds = array_map(fn ($id) => ['publicationId' => $id], $removePublication);
                $this->updateSalesChannelUnpublishing($productId, $removePublicationIds, $credential);
            }
        }
    }

    /**
     * Remove Sales Channel Publishing
     *
     * */
    public function updateSalesChannelUnpublishing(string $productId, array $salesChannel, array $credential): void
    {
        $productUnpublishFormate = [
            'id'                  => $productId,
            'productPublications' => $salesChannel,
        ];

        $this->requestGraphQlApiAction('productUnpublish', $credential, ['input' => $productUnpublishFormate]);
    }

    /**
     * Processes product updates in Shopify, handling both parent and variant product updates.
     *
     * */
    private function processProductUpdate(
        bool $skipParent,
        array $formattedGraphqlData,
        string $productId,
        array $parentMapping,
        array $mapping,
        array $parentData,
        array $rowData,
        array $imageData,
        array $variantData,
        array $variableOption,
        array $mediaMappings,
        array $finalOption,
    ): array|null|bool {
        $productOption = [];
        $productOptionExist = [];
        if (! $skipParent) {
            $mediaType = $mediaMappings['mediaType'] ?? null;
            $galleryAttr = false;
            $attrImage = $mediaMappings['mediaAttributes'] ?? null;

            if ($attrImage) {
                $mediaAttr = explode(',', $attrImage);

                if ($mediaType == 'gallery') {
                    $galleryAttr = true;
                }

                $mediaAttr = array_merge($mediaAttr, $this->assetAttr); // Working on this point
                $allimageAttr = $this->getAllImageMappingBySku('productImage', $productId, $mediaAttr, $galleryAttr);
                $deleteIds = array_merge(array_column($allimageAttr, 'externalId'), $this->removeImgAttr);
                if (! empty($deleteIds)) {
                    $this->requestGraphQlApiAction('productDeleteMedia', $this->credentialAsArray, [
                        'mediaIds'  => $deleteIds,
                        'productId' => $productId,
                    ]);

                    $this->deleteProductMediaMapping($deleteIds);
                }
            }

            $result = $this->updateProductWithMetafields($formattedGraphqlData, $this->credentialAsArray, $productId, $parentMapping, $mapping, $parentData, $rowData);

            $productOptionExist = $result['body']['data']['productUpdate']['product']['options'] ?? [];
            $productOptionExist = array_column($productOptionExist, 'name') ?? [];
            $errorUpdate = $result['body']['data']['productUpdate']['userErrors'] ?? [];
            if (isset($errorUpdate[0]['message']) && $errorUpdate[0]['message'] == self::NOT_EXIST_PRODUCT) {
                $this->deleteProductMapping($productId);
                if (! empty($parentData)) {
                    $rowData['parent'] = $parentData;
                }

                $notExistProductCreated = $this->processProductData($rowData);

                if ($notExistProductCreated) {
                    return true;
                }
            }

            if (! empty($errorUpdate)) {
                $metafieldProduct = $formattedGraphqlData['parentMetaFields'] ?? $formattedGraphqlData['metafields'];
                if (! empty($metafieldProduct)) {
                    $this->prependAttributeCodesToErrors($errorUpdate, $metafieldProduct);
                }
                $this->logWarning($errorUpdate, $parentData['sku'] ?? $rowData['sku']);
                $this->skippedItemsCount++;

                return null;
            }

            if (! $result) {
                return null;
            }
        }

        $this->handleMediaUpdates($productId, $rowData, $parentData, $imageData);
        if (empty($parentMapping)) {
            $this->handleAfterApiRequest($rowData, $result, $mapping, $this->export->id, $formattedGraphqlData);
            $variants = $result['body']['data']['productUpdate']['product']['variants']['edges'];
            foreach ($variants as $variant) {
                $variantId = $variant['node']['id'];
                $inventoryData = $variantData['inventoryQuantities'];
                unset($variantData['inventoryQuantities']);
                $variantDataFormatted = [
                    'productId' => $productId,
                    'variants'  => array_merge($variant['node'], $variantData),
                ];

                $defaultVariant = $this->requestGraphQlApiAction(self::VARIANT_UPDATE, $this->credentialAsArray, $variantDataFormatted);
                $productVariant = $defaultVariant['body']['data'][self::VARIANT_UPDATE] ?? [];
                $inventoryToLocations = $productVariant['productVariants'][0]['inventoryItem']['inventoryLevels']['edges'] ?? [];
                $inventoryItemId = $productVariant['productVariants'][0]['inventoryItem']['id'];
                $addedQuantity = (int) $inventoryData['availableQuantity'] - (int) $productVariant['productVariants'][0]['inventoryQuantity'];
                foreach ($inventoryToLocations as $inventoryToLocation) {
                    $this->updateInventoryValue($inventoryToLocation['node']['location']['id'], $inventoryItemId, $addedQuantity);
                }
            }
        } else {
            $needToAdd = array_diff(array_column($finalOption, 'name'), $productOptionExist);
            if (! empty($needToAdd)) {
                $filteredOptions = array_values(array_filter($finalOption, function ($option) use ($needToAdd) {
                    return in_array($option['name'], $needToAdd);
                }));

                $formateOptCreate = [
                    'productId' => $productId,
                    'options'   => $filteredOptions,
                ];
                $optionResult = $this->requestGraphQlApiAction('createOptions', $this->credentialAsArray, $formateOptCreate);
                $productOption = $optionResult['body']['data']['productOptionsCreate']['product']['options'];
            }

            $productOption = $this->updateProductOptions($parentData, $variableOption);
            if (! empty($this->updateMedia) && empty($variantData['mediaId'])) {
                $key = count($imageData[$parentData['sku'] ?? ''] ?? []);
                if (! empty($this->updateMedia[$key]['id'])) {
                    $variantData['mediaId'] = $this->updateMedia[$key]['id'];
                }
            }

            $variantResult = $this->updateProductVariant(
                $variantData,
                $formattedGraphqlData,
                $mapping,
                $productId,
                $rowData,
                $parentData
            );

            if (! $variantResult) {
                return null;
            }

            $variantId = $mapping[0]['externalId'];
        }

        return [
            'variantId'      => $variantId,
            'optionsGetting' => $productOption,
        ];
    }

    /**
     * Formats data to be used for a GraphQL request to Shopify.
     * */
    public function formatGraphqlData(
        array $mergedFields,
        array $parentMergedFields,
        array $finalCategories,
        array $finalOption,
        array $parentMapping
    ): array {
        $formattedGraphqlData = $this->shopifyGraphQLDataFormatter->formatDataForGraphql($mergedFields, $this->exportMapping->mapping ?? [], $this->shopifyDefaultLocale, $parentMergedFields, $this->productMetaFieldMapping, $this->variantMetaFieldMapping);
        $this->metaFieldAttributeCode = $this->metafieldTranslationFormate($this->productMetaFieldMapping);
        $this->variantMetafieldAttrCode = $this->metafieldTranslationFormate($this->variantMetaFieldMapping);

        $finalCategories = array_filter($finalCategories);
        $formattedGraphqlData['collectionsToJoin'] = $finalCategories;

        if (! empty($parentMergedFields) && empty($parentMapping)) {
            $formattedGraphqlData['productOptions'] = $finalOption;
        }

        return $formattedGraphqlData;
    }

    public function metafieldTranslationFormate(array $metafield): array
    {
        return array_combine(
            array_column($metafield, 'name_space_key') ?? [],
            array_column($metafield, 'code') ?? []
        );
    }

    /**
     * Checks if the API result contains errors
     * */
    public function checkNotExistError(array $result): bool
    {
        if (isset($result['body']['errors'])) {
            $error = json_encode($result['body']['errors']);
            $this->jobLogger->warning($error);

            return false;
        }

        return true;
    }

    /**
     * Checks if the API result contains errors
     * */
    public function logWarning(array $data, string $identifier): void
    {
        if (! empty($data) && ! empty($identifier)) {
            $error = json_encode($data);
            $this->jobLogger->warning(
                "Warning for product with SKU: {$identifier}, : {$error}"
            );
        }
    }

    /**
     * for parentMetafield Array
     * */
    public function prependAttributeCodesToErrors(array &$errorUpdate, array $metafields): void
    {
        $metafieldErrorIndexes = array_map(function ($error) {
            return $error['field'][1] ?? null;
        }, array_filter($errorUpdate, function ($error) {
            return isset($error['field'][0]) && $error['field'][0] === 'metafields';
        }));

        if (! empty($metafieldErrorIndexes)) {
            $attrCode = array_map(function ($index) use ($metafields) {
                return isset($metafields[$index]['key']) ? $metafields[$index]['key'] : null;
            }, $metafieldErrorIndexes);

            $errorUpdate['attrcode'] = $attrCode;
        }
    }

    /**
     * for variant Metafield Array
     * */
    public function variantMetafieldAttributeCodeError(&$error, array $variantMetaField, array $metafields): void
    {
        $variantMetafieldError = array_map(function ($error) {
            return $error['field'][3] ?? null;
        }, array_filter($variantMetaField, function ($error) {
            return isset($error['field'][2]) && $error['field'][2] === 'metafields';
        }));

        if (! empty($variantMetafieldError)) {
            $attrCode = array_map(function ($index) use ($metafields) {
                return isset($metafields[$index]['key']) ? $metafields[$index]['key'] : null;
            }, $variantMetafieldError);
            $error['metafieldDefinition'] = [
                'attrcode' => $attrCode,
            ];
        }
    }

    /**
     * Update product with Metafields
     * */
    public function updateProductWithMetafields(
        array $formattedGraphqlData,
        array $credentialAsArray,
        string $productId,
        array $parentMapping,
        array $mapping,
        array $parentData = [],
        array $rowData = []
    ): ?array {
        $result = $this->apiRequestShopifyProduct($formattedGraphqlData, $credentialAsArray, $productId);

        if (! $this->checkNotExistError($result)) {
            return null;
        }

        $existingPublicationId = $result['body']['data']['productUpdate']['product']['resourcePublications']['edges'] ?? [];

        $this->updateSalesChannelPublishing($productId, $existingPublicationId, $this->publicationId, $credentialAsArray);

        if (! empty($result['body']['data']['productUpdate']['userErrors'])) {
            return $result;
        }

        $sku = $parentData['sku'] ?? $rowData['sku'];
        $mappingId = $parentMapping[0]['id'] ?? $mapping[0]['id'];
        $this->updateMapping($sku, $productId, $this->export->id, $mappingId);
        if (! empty($parentData)) {
            $this->productOptions[$parentData['sku']] = $result['body']['data']['productUpdate']['product']['options'];
        }

        return $result;
    }

    /**
     * Handles the translation
     * */
    public function handleChildProductTranslation(
        array $parentData,
        array $mergedFields,
        bool $skipParent,
        ?array $optionsGetting,
        array $optionValuesTranslation,
        string $productId,
        ?string $variantId,
        array $rowData
    ): void {
        if (! empty($parentData)) {
            $childValues = array_values(array_intersect(array_values($this->variantMetafieldAttrCode), array_keys($mergedFields)));

            if (! $skipParent) {
                $this->updateProductOptionsTranslation(
                    $this->shopifyDefaultLocale,
                    $optionsGetting,
                    $parentData['super_attributes'],
                    $this->credential,
                    $this->credentialAsArray
                );
            }

            $this->updateProductOptionValuesTranslation(
                $this->shopifyDefaultLocale,
                $optionsGetting,
                $optionValuesTranslation,
                $this->credential,
                $this->credentialAsArray
            );

            $addedMetafieldsInVariant = $this->getExisitingMetafields($this->credentialAsArray, $productId, $variantId);

            $this->metafieldTranslation(
                $this->shopifyDefaultLocale,
                $this->jobChannel,
                $rowData,
                $addedMetafieldsInVariant,
                $childValues,
                $this->credential,
                $this->credentialAsArray,
                $this->variantMetafieldAttrCode
            );
        }
    }

    /**
     * Handles the processing of product translations for a given product ID.
     *  */
    public function handleProductProcessingForTranslation(
        string $productId,
        array $parentMergedFields,
        array $mergedFields,
        array $parentData,
        array $rowData,
        array $formattedGraphqlData
    ): void {
        if (! empty($productId) && ! in_array($productId, $this->productId)) {
            $this->productId[] = $productId;

            $productData = ! empty($parentMergedFields) ? $parentMergedFields : $mergedFields;
            $productItem = ! empty($parentData) ? $parentData : $rowData;
            $parentValues = array_values(array_intersect(array_values($this->metaFieldAttributeCode), array_keys($productData)));
            $addedMetafields = $this->getExisitingMetafields($this->credentialAsArray, $productId, null);

            $filteredMetafields = array_filter($addedMetafields, function ($item) {
                return ! in_array($item['node']['key'], ['description_tag', 'title_tag']);
            });

            $filteredMetafields = array_values($filteredMetafields);
            $this->metafieldTranslation(
                $this->shopifyDefaultLocale,
                $this->jobChannel,
                $productItem,
                $filteredMetafields,
                $parentValues,
                $this->credential,
                $this->credentialAsArray,
                $this->metaFieldAttributeCode,
            );

            $matchedAttr = array_intersect_key(
                $this->exportMapping->mapping['shopify_connector_settings'] ?? [],
                array_flip($this->translationShopifyFields)
            );

            $this->productTranslation(
                $productId,
                $this->shopifyDefaultLocale,
                $this->jobChannel,
                $productItem,
                $this->credential,
                $this->credentialAsArray,
                $formattedGraphqlData,
                $matchedAttr
            );
        }
    }

    /**
     * Updates a product variant with the provided data.
     * */
    public function updateProductVariant(
        array $variantData,
        array $formattedGraphqlData,
        array $mapping,
        string $productId,
        array $rowData,
        ?array $parentData
    ): string|bool|null {
        $variantData['id'] = $variantId = $mapping[0]['externalId'];
        if (isset($formattedGraphqlData['metafields'])) {
            $variantData['metafields'] = $formattedGraphqlData['metafields'];
        }
        $inventoryData = $variantData['inventoryQuantities'] ?? [];
        unset($variantData['inventoryQuantities']);
        $variantInput = [
            'productId' => $productId,
            'variants'  => [$variantData],
        ];

        $result = $this->requestGraphQlApiAction(self::VARIANT_UPDATE, $this->credentialAsArray, $variantInput);
        $productVariant = $result['body']['data'][self::VARIANT_UPDATE] ?? [];
        $errors = array_column($productVariant['userErrors'] ?? [], 'message');
        if (in_array(self::NOT_EXIST_PRODUCT_VARIANT, $errors)) {
            $this->deleteProductVariantMapping($variantId, $rowData['sku']);
            if (! empty($parentData)) {
                $rowData['parent'] = $parentData;
            }

            $notExistProductCreated = $this->processProductData($rowData);

            if ($notExistProductCreated) {
                return true;
            }
        }

        if (! empty($errors)) {
            if (! empty($formattedGraphqlData['metafields'])) {
                $this->variantMetafieldAttributeCodeError($errors, $productVariant['userErrors'], $formattedGraphqlData['metafields']);
            }
            $this->logWarning($errors, $rowData['sku']);
            $this->skippedItemsCount++;

            return null;
        }

        if (! $this->checkNotExistError($result)) {
            return null;
        }

        $inventoryToLocations = $productVariant['productVariants'][0]['inventoryItem']['inventoryLevels']['edges'] ?? [];
        $inventoryItemId = $productVariant['productVariants'][0]['inventoryItem']['id'];
        $addedQuantity = (int) $inventoryData['availableQuantity'] - (int) $productVariant['productVariants'][0]['inventoryQuantity'];
        foreach ($inventoryToLocations as $inventoryToLocation) {
            $this->updateInventoryValue($inventoryToLocation['node']['location']['id'], $inventoryItemId, $addedQuantity);
        }

        $updatedVariantId = $productVariant['productVariants'][0]['id'];

        $this->updateMapping($rowData['sku'], $updatedVariantId, $this->export->id, $mapping[0]['id']);

        return $updatedVariantId;
    }

    /**
     * Updates product inventorty location value
     *
     * */
    public function updateInventoryValue($locationId, $inventoryId, $inventoryValue): void
    {
        $input = [
            'input' => [
                'reason'               => 'correction',
                'name'                 => 'available',
                'referenceDocumentUri' => 'logistics://some.warehouse/take/2023-01/13',
                'changes'              => [
                    [
                        'delta'           => $inventoryValue,
                        'inventoryItemId' => $inventoryId,
                        'locationId'      => $locationId,
                    ],
                ],
            ],
        ];

        $this->requestGraphQlApiAction('inventoryAdjustQuantities', $this->credentialAsArray, $input);
    }

    /**
     * Updates product options for a given parent product.
     *
     * */
    public function updateProductOptions(array $parentData, array $variableOption): array
    {
        $optionsGetting = [];

        foreach ($this->productOptions[$parentData['sku']] ?? [] as $key => $value) {
            $variableOption[$key]['optionInput']['id'] = $value['id'];
            $names = array_column($value['optionValues'], 'name');

            if (in_array($variableOption[$key]['optionValuesToUpdate'][0]['name'], $names)) {
                $index = array_search($variableOption[$key]['optionValuesToUpdate'][0]['name'], $names);
                $variableOption[$key]['optionValuesToUpdate'][0]['id'] = $value['optionValues'][$index]['id'];
            } else {
                $variableOption[$key]['optionValuesToAdd'][0]['name'] = $variableOption[$key]['optionValuesToUpdate'][0]['name'];
                unset($variableOption[$key]['optionValuesToUpdate']);
            }

            $optionResult = $this->requestGraphQlApiAction('productOptionUpdated', $this->credentialAsArray, $variableOption[$key]);
            $optionsGetting = $optionResult['body']['data']['productOptionUpdate']['product']['options'];

            $this->productOptions[$parentData['sku']] = $optionsGetting;
        }

        return $optionsGetting;
    }

    /**
     * Handles media updates for a Shopify product.
     * */
    public function handleMediaUpdates(
        string $productId,
        array $rowData,
        array $parentData,
        array $imageData,
    ): void {
        if (! empty($this->updateMedia)) {
            $jsonData = ['input' => $this->updateMedia];
            $fileUpdate = $this->requestGraphQlApiAction('productFileUpdate', $this->credentialAsArray, $jsonData);
            $errors = $fileUpdate['body']['data']['fileUpdate']['userErrors'] ?? [];
            $errorCode = array_column($errors, 'code');
            if (in_array('FILE_DOES_NOT_EXIST', $errorCode)) {
                preg_match('/^File ids \[(.*?)\]/', $errors[0]['message'], $matches);
                if (! empty($matches[1])) {
                    $fileIds = json_decode('['.$matches[1].']', true);
                    $this->deleteProductMediaMapping($fileIds);
                }
            }
        }

        if (! empty($this->imageData)) {
            $newImageAdded = [
                'productId' => $productId,
                'media'     => $this->imageData,
            ];
            $resultImage = $this->requestGraphQlApiAction('productCreateMedia', $this->credentialAsArray, $newImageAdded);
            $mediasUpdate = $this->updateMedia = $resultImage['body']['data']['productCreateMedia']['media'];

            if (! empty($parentData) && ! empty($imageData[$parentData['sku']])) {
                $this->mapMediaImages($parentData, $mediasUpdate, $productId, $imageData, $this->parentImageAttr);
            }

            if (! empty($mediasUpdate) && ! empty($imageData[$rowData['sku']])) {
                $this->mapMediaImages($rowData, $mediasUpdate, $productId, $imageData, $this->childImageAttr);
            }
        }
    }

    /**
     * Maps media images to a Shopify product and updates the media information.
     * */
    private function mapMediaImages(array $data, array &$mediasUpdate, string $productId, array $imageData, array $mappingImageAttr): void
    {
        foreach ($imageData[$data['sku']] ?? [] as $key => $imageUrl) {
            $this->imageMapping(
                'productImage',
                $mappingImageAttr[$key],
                $mediasUpdate[$key]['id'],
                $this->export->id,
                $productId,
                $data['sku']
            );

            unset($mediasUpdate[$key]);
        }

        $mediasUpdate = array_values(array_filter($mediasUpdate));
    }

    /**
     * Formats and creates a Shopify product variant with the provided data.
     * */
    public function productVariantFormatAndCreate(
        array $formattedGraphqlData,
        array $variantData,
        array $imageData,
        array $rowData,
        array $productOptionValues,
        string $parentId,
        ?string $variantMediaId = null,
    ) {
        if (isset($formattedGraphqlData['metafields'])) {
            $variantData['metafields'] = $formattedGraphqlData['metafields'];
        }

        if ($imageData && isset($imageData[$rowData['sku']])) {
            $variantData['mediaSrc'] = reset($imageData[$rowData['sku']])['originalSource'] ?? '';
            $finalVariant['media'] = $imageData[$rowData['sku']];
        }

        if ($variantMediaId) {
            $variantData['mediaId'] = $variantMediaId;
        }

        $finalVariant['variantsInput'] = $variantData + $productOptionValues;
        $finalVariant['productId'] = $parentId;

        if (empty($finalVariant['media'])) {
            $finalVariant['media'] = [];
        }

        $related = $this->getAllImageMappingBySku('product', $parentId);

        if (empty($related)) {
            $finalVariant['strategy'] = 'REMOVE_STANDALONE_VARIANT';
        }

        $result = $this->requestGraphQlApiAction('CreateProductVariants', $this->credentialAsArray, $finalVariant);

        return $result;
    }

    /**
     * Processes the result of product variant creation and handles associated data such as media mapping and options.
     */
    public function processVariantCreationResult(
        array $formattedGraphqlData,
        array $variantData,
        array $imageData,
        array $rowData,
        array $productOptionValues,
        array $parentMapping
    ): ?array {
        $result = $this->productVariantFormatAndCreate(
            $formattedGraphqlData,
            $variantData,
            $imageData,
            $rowData,
            $productOptionValues,
            $parentMapping[0]['externalId']
        );

        $variantErrorResult = $result['body']['data'][self::VARIANT_CREATE]['userErrors'] ?? [];
        if (! empty($variantErrorResult)) {
            $errors = array_column($variantErrorResult, 'message');
            if (! empty($formattedGraphqlData['metafields'])) {
                $this->variantMetafieldAttributeCodeError($errors, $variantErrorResult, $formattedGraphqlData['metafields']);
            }
            $this->logWarning($errors, $rowData['sku']);
            $this->skippedItemsCount++;

            return null;
        }

        $variantId = $result['body']['data'][self::VARIANT_CREATE]['productVariants'][0]['id'];

        $optionsGetting = $result['body']['data'][self::VARIANT_CREATE]['product']['options'];

        $productId = $result['body']['data'][self::VARIANT_CREATE]['product']['id'];

        if ($imageData && isset($imageData[$rowData['sku']])) {
            $medias = array_slice(
                $result['body']['data'][self::VARIANT_CREATE]['product']['media']['nodes'],
                -count($imageData[$rowData['sku']])
            );

            if (! empty($medias)) {
                foreach ($imageData[$rowData['sku']] as $key => $imageUrl) {
                    $this->imageMapping(
                        'productImage',
                        $this->imageAttributes[$key],
                        $medias[$key]['id'],
                        $this->export->id,
                        $parentMapping[0]['externalId'],
                        $rowData['sku']
                    );
                }
            }
        }

        $this->parentMapping($rowData['sku'], $variantId, $this->export->id, $productId);

        return [
            'variantId'      => $variantId,
            'optionsGetting' => $optionsGetting,
            'productId'      => $productId,
        ];
    }

    /**
     * Maps the provided image IDs to product or variant images for both the child and parent products.
     */
    public function imageIdMapping(
        array $imageIds,
        array $imageData,
        array $rowData,
        array $parentData,
        string $productId
    ): void {
        foreach ($imageData[$parentData['sku'] ?? ''] ?? [] as $key => $imageUrl) {
            $this->imageMapping('productImage', $this->parentImageAttr[$key] ?? $this->imageAttributes[$key], $imageIds[$key]['id'], $this->export->id, $productId, $parentData['sku']);

            unset($imageIds[$key]['id']);
        }

        $imageIds = array_values(array_filter($imageIds));
        foreach ($imageData[$rowData['sku']] ?? [] as $key => $imageUrl) {
            $this->imageMapping('productImage', $this->childImageAttr[$key], $imageIds[$key]['id'], $this->export->id, $productId, $rowData['sku']);
        }
    }

    /**
     * Retrieves category external IDs based on the provided category codes.
     */
    public function getCategoriesByCode(array $categoriesCode, array &$finalCategories): void
    {
        foreach ($categoriesCode ?? [] as $key => $value) {
            $check = $this->checkMappingInDb(['code' => $value], 'category');
            if (isset($check[0]['externalId'])) {
                $finalCategories[] = $check[0]['externalId'];
            }
        }
    }

    /**
     * process super attributes
     */
    private function processSuperAttributes(
        array $superAttributes,
        string $shopifyDefaultLocale,
        array $mergedFields,
        ?array $parentMapping,
        ?array $mapping
    ): ?array {
        $optionsValues = ['optionValues' => []];

        $variableOption = [];

        $finalOption = [];

        foreach ($superAttributes as $key => $optionvalues) {
            $translationsOption = $optionvalues['translations'];
            $name = $optionvalues['code'];
            if (isset($this->settingMapping->mapping['option_name_label']) && $this->settingMapping->mapping['option_name_label']) {
                $name = array_column(array_filter($translationsOption, fn ($item) => $item['locale'] === $shopifyDefaultLocale), 'name')[0] ?? $optionvalues['name'];
            }

            if ($key < 3) {
                $options = [
                    'name'   => $name,
                    'values' => [['name' => $mergedFields[$optionvalues['code']]]],
                ];
                $finalOption[] = $options;
            }

            $attribute = $this->attributesAll[$optionvalues['code']] ?? null;

            $optionTrans = $attribute->options()->where('code', '=', $mergedFields[$optionvalues['code']])->first()->toArray();

            $optionsValues['optionValues'][] = [
                'name'       => $mergedFields[$optionvalues['code']],
                'optionName' => $name,
            ];

            $optionValuesTranslation[$mergedFields[$optionvalues['code']]] = $optionTrans['translations'];

            if (! empty($parentMapping) && ! empty($mapping)) {
                $optionValuesToUpdate = [
                    [
                        'id'   => null,
                        'name' => $mergedFields[$optionvalues['code']],
                    ],
                ];

                $variableOption[] = [
                    'productId'   => $parentMapping[0]['externalId'],
                    'optionInput' => [
                        'id'   => null,
                        'name' => $name,
                    ],
                    'optionValuesToUpdate' => $optionValuesToUpdate,
                ];
            }
        }

        return [
            $variableOption,
            $optionsValues,
            $finalOption,
            $optionValuesTranslation,
        ];
    }

    /**
     * getting all attribute values
     */
    public function gettingAllAttrValue(array $rowData): array
    {
        $commonFields = $this->getCommonFields($rowData);

        $commonFields['status'] = $rowData['status'] == 1 ? 'true' : 'false';

        $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $this->shopifyDefaultLocale);

        $channelSpecificFields = $this->getChannelSpecificFields($rowData, $this->jobChannel);

        $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($rowData, $this->jobChannel, $this->shopifyDefaultLocale);

        return array_merge($commonFields, $localeSpecificFields, $channelSpecificFields, $channelLocaleSpecificFields);
    }

    /**
     * Sends a request to the Shopify API to create or update a product.
     *
     *  */
    public function apiRequestShopifyProduct(array $formattedGraphqlData, array $credential, ?string $id = null): ?array
    {
        if (isset($formattedGraphqlData['parentMetaFields'])) {
            $formattedGraphqlData['metafields'] = $formattedGraphqlData['parentMetaFields'];
            unset($formattedGraphqlData['parentMetaFields']);
        }

        if ($id) {
            $formattedGraphqlData['id'] = $id;
            $response = $this->requestGraphQlApiAction('productUpdate', $credential, ['product' => $formattedGraphqlData]);
        } else {
            $response = $this->requestGraphQlApiAction('createProduct', $credential, ['product' => $formattedGraphqlData, 'media' => $this->imageData]);
        }

        return $response;
    }

    /**
     * Creates a new product variant in Shopify and deletes the original variant.
     *
     * */
    public function apiRequestShopifyDefaultVariantCreate(array $variantData, array $credential): ?array
    {
        $response = $this->requestGraphQlApiAction('CreateProductVariantsDefault', $credential, $variantData);

        return $response;
    }

    /**
     * Retrieves existing metafields for a specified product or variant from the Shopify API.
     */
    public function getExisitingMetafields(array $credential, string $productId, ?string $variantId): ?array
    {
        if ($variantId) {
            $endPoint = 'productVariantMetafield';
            $variable = [
                'id' => $variantId,
            ];
        }

        $existingMetaFields = [];
        $url = null;
        $first = 30;
        do {
            if (! $url) {
                $endPoint = 'productMetafields';
                $variable = [
                    'id'     => $productId,
                    'first'  => $first,
                ];
                $productType = 'product';

                if ($variantId) {
                    $endPoint = 'productVariantMetafield';
                    $variable = [
                        'id'     => $variantId,
                        'first'  => $first,
                    ];
                    $productType = 'productVariant';
                }
            } else {
                $endPoint = 'productMetafieldsByCursor';
                $variable = [
                    'id'          => $productId,
                    'first'       => $first,
                    'afterCursor' => $url,
                ];
                $productType = 'product';

                if ($variantId) {
                    $endPoint = 'productVariantMetafieldByCursor';
                    $variable = [
                        'id'          => $variantId,
                        'first'       => $first,
                        'afterCursor' => $url,
                    ];

                    $productType = 'productVariant';
                }
            }

            $response = $this->requestGraphQlApiAction($endPoint, $credential, $variable, $productType);

            if (! isset($response['body']['data'][$productType]['metafields'])) {
                return [];
            }

            $gettingMetaFields = $response['body']['data'][$productType]['metafields']['edges'];

            if (! empty($gettingMetaFields)) {
                $existingMetaFields = array_merge($existingMetaFields, $gettingMetaFields);
            }

            if ($first != count($gettingMetaFields)) {
                break;
            }

            $lastElement = end($gettingMetaFields);
            $lastCursor = $lastElement['cursor'] ?? null;

            if (isset($gettingMetaFields) && $url !== $lastCursor) {
                $url = $lastCursor;
            }
        } while ($gettingMetaFields);

        return $existingMetaFields;
    }

    /**
     * Handles Product images.
     */
    public function formatImageDataForGraphqlImage(array $rawData, array $mediaMapping, array $parentRawData): array
    {
        $medias = [];
        $imageAttrCode = [];
        $parentImageAttrCode = [];
        $assetAttrCode = [];
        $updateMedia = [];

        if (! isset($mediaMapping['mediaAttributes']) || empty($mediaMapping['mediaAttributes'])) {
            return $medias;
        }

        $imagesAttr = explode(',', $mediaMapping['mediaAttributes']);
        foreach ($imagesAttr as $imageAttr) {
            // Process parent data
            if (! empty($parentRawData[$imageAttr])) {
                $medias = $this->handleImageAttribute(
                    $imageAttr,
                    $parentRawData,
                    $parentImageAttrCode,
                    $updateMedia,
                    $medias,
                    $assetAttrCode
                );
            } else {
                $this->removeIfMappedInDb($imageAttr, $parentRawData['sku'] ?? null);
            }

            // Process child data
            if (! empty($rawData[$imageAttr])) {
                $medias = $this->handleImageAttribute(
                    $imageAttr,
                    $rawData,
                    $imageAttrCode,
                    $updateMedia,
                    $medias,
                    $assetAttrCode
                );
            } else {
                $this->removeIfMappedInDb($imageAttr, $rawData['sku'] ?? null);
            }
        }

        $this->assetAttr = $assetAttrCode;
        $this->childImageAttr = $imageAttrCode;
        $this->parentImageAttr = $parentImageAttrCode;
        $this->imageAttributes = array_merge($parentImageAttrCode, $imageAttrCode);
        $this->updateMedia = $updateMedia;

        return $medias;
    }

    private function handleImageAttribute(
        string $imageAttr,
        array $data,
        array &$imageAttrCode,
        array &$updateMedia,
        array $medias,
        array &$assetAttrCode
    ): array {
        $attrType = $this->attributesAll[$imageAttr]->type ?? null;
        if ($attrType === 'asset') {
            $ids = explode(',', $data[$imageAttr]);
            $assets = $this->assetRepository?->whereIn('id', $ids)?->get()?->toArray();
            foreach ($assets ?? [] as $asset) {
                $imageKey = $imageAttr.'_'.$asset['id'];
                $assetAttrCode[] = $imageKey;
                if ($asset['mime_type'] == 'video/mp4') {
                    $videoInstance = $this->videoAddToShopify($asset, $data['sku'], $medias, $imageKey, $updateMedia);
                    if (! empty($videoInstance)) {
                        $imageAttrCode[] = $imageKey;
                    }

                    continue;
                } elseif (in_array($asset['mime_type'], $this->imageMineType)) {
                    $medias = $this->processMedia($imageKey, $data, $imageAttrCode, $updateMedia, $medias, $asset['path']);
                } else {
                    continue;
                }
            }

            $this->removeImgAttr = $this->removeAssetsImages($imageAttr, $data);
        } else {
            $medias = $this->processMedia($imageAttr, $data, $imageAttrCode, $updateMedia, $medias);
        }

        return $medias;
    }

    private function removeIfMappedInDb(string $imageAttr, ?string $sku): void
    {
        if ($sku) {
            $mapping = $this->checkMappingInDbForImage($imageAttr, 'productImage', $sku);
            $this->removeImgAttr[] = $mapping[0]['externalId'] ?? null;
        }
    }

    private function videoAddToShopify($asset, $sku, &$medias, $imageAttrKey, &$updateMedia)
    {
        $mappingImage = $this->checkMappingInDbForImage($imageAttrKey, 'productImage', $sku);
        if (! empty($mappingImage)) {
            return [];
        }
        $fileCreateForMp4 = [
            'filename'  => $asset['file_name'],
            'mimeType'  => $asset['mime_type'],
            'resource'  => strtoupper($asset['file_type']),
            'fileSize'  => (string) $asset['file_size'],
        ];

        $videoResponse = $this->requestGraphQlApiAction('stagedUploadsCreate', $this->credentialAsArray, [
            'input' => $fileCreateForMp4,
        ]);

        $stagedTargets = $videoResponse['body']['data']['stagedUploadsCreate']['stagedTargets'] ?? [];
        foreach ($stagedTargets as $stagedTarget) {
            $multipart = [];
            foreach ($stagedTarget['parameters'] as $param) {
                $multipart[] = [
                    'name'     => $param['name'],
                    'contents' => $param['value'],
                ];
            }

            $filePath = base_path('storage/app/private/'.$asset['path']);

            if (! file_exists($filePath) || ! is_readable($filePath)) {
                throw new \Exception('File does not exist or not Readable at path: '.$filePath);
            }

            $multipart[] = [
                'name'     => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => $asset['file_name'],
                'headers'  => [
                    'Content-Type' => $asset['mime_type'],
                ],
            ];

            $response = Http::withOptions([
                'headers' => ['Accept' => '*/*'], // Optional but safe
            ])->asMultipart()->post($stagedTarget['url'], $multipart);

            if ($response->failed()) {
                return [];
            }

            $medias[$sku][] = [
                'mediaContentType' => 'VIDEO',
                'originalSource'   => $stagedTarget['resourceUrl'],
            ];

            return $medias;
        }
    }

    /**
     * Handles Product gallery images.
     */
    public function formatGalleryDataForGraphqlImage(array $rawData, array $mediaMapping, array $parentRawData, bool $skipParent): array
    {
        $medias = [];

        if (empty($mediaMapping['mediaAttributes'])) {
            return $medias;
        }

        $imageAttrs = explode(',', $mediaMapping['mediaAttributes']);
        $imageAttrCode = [];
        $parentImageAttrCode = [];
        $updateMedia = [];
        $allRemoveGallery = [];

        foreach ($imageAttrs as $imageAttr) {
            // Process child data
            if (! empty($rawData)) {
                $this->processGalleryAttribute(
                    $rawData,
                    $imageAttr,
                    $imageAttrCode,
                    $updateMedia,
                    $medias,
                    $allRemoveGallery
                );
            }

            // Process parent data
            if (! empty($parentRawData) && ! $skipParent) {
                $this->processGalleryAttribute(
                    $parentRawData,
                    $imageAttr,
                    $parentImageAttrCode,
                    $updateMedia,
                    $medias,
                    $allRemoveGallery
                );
            }
        }

        $this->imageAttributes = $this->childImageAttr = array_values(array_unique($imageAttrCode));
        $this->parentImageAttr = array_values(array_unique($parentImageAttrCode));
        $this->removeImgAttr = $allRemoveGallery;
        $this->updateMedia = $updateMedia;

        return $medias;
    }

    private function processGalleryAttribute(
        array $data,
        string $imageAttr,
        array &$imageAttrCode,
        array &$updateMedia,
        array &$medias,
        array &$allRemoveGallery
    ): void {
        if (empty($data[$imageAttr])) {
            $allRemoveGallery = array_merge($allRemoveGallery, $this->removeEmptyGallery($imageAttr, $data));

            return;
        }

        $attrType = $this->attributesAll[$imageAttr]?->type ?? null;

        if ($attrType === 'asset') {
            $ids = explode(',', $data[$imageAttr]);
            $assets = $this->assetRepository?->whereIn('id', $ids)?->get()?->toArray();
            foreach ($assets ?? [] as $asset) {
                $imageAttrKey = $imageAttr.'_'.$asset['id'];
                if ($asset['mime_type'] == 'video/mp4') {
                    $videoInstance = $this->videoAddToShopify($asset, $data['sku'], $medias, $imageAttrKey, $updateMedia);
                    if (! empty($videoInstance)) {
                        $imageAttrCode[] = $imageAttrKey;
                    }

                    continue;
                } elseif (in_array($asset['mime_type'], $this->imageMineType)) {
                    $medias = $this->processMedia($imageAttrKey, $data, $imageAttrCode, $updateMedia, $medias, $asset['path']);
                } else {
                    continue;
                }
            }

            $allRemoveGallery = array_merge($allRemoveGallery, $this->removeAssetsImages($imageAttr, $data));
        } else {
            $allRemoveGallery = array_merge($allRemoveGallery, $this->removeGalleryImages($imageAttr, $data));
            $medias = $this->processGallery($imageAttr, $data, $imageAttrCode, $updateMedia, $medias);
        }
    }

    public function removeGalleryImages(string $galleryAttr, array $itemData)
    {
        $mappingGallery = $this->checkMappingInDbForGallery($galleryAttr, 'productImage', $itemData['sku']);
        $removeGalleryAttr = [];
        foreach ($mappingGallery as $key => $galley) {
            if (! isset($itemData[$galleryAttr][$key])) {
                $removeGalleryAttr[] = $galley['externalId'] ?? null;
            }
        }

        return $removeGalleryAttr;
    }

    public function removeAssetsImages(string $galleryAttr, array $itemData)
    {
        $mappingGallery = $this->checkMappingInDbForGallery($galleryAttr, 'productImage', $itemData['sku'], $asset = true);
        $assetIds = explode(',', $itemData[$galleryAttr]);
        $removeGalleryAttr = [];
        foreach ($mappingGallery as $key => $galley) {
            if (! in_array((string) $key, $assetIds)) {
                $removeGalleryAttr[] = $galley['externalId'] ?? null;
            }
        }

        return $removeGalleryAttr;
    }

    public function removeEmptyGallery(string $galleryAttr, array $itemData): array
    {
        $mappingGallery = $this->checkMappingInDbForGallery($galleryAttr, 'productImage', $itemData['sku']);
        $removeGalleryAttr = array_column($mappingGallery, 'externalId');

        return $removeGalleryAttr ?? [];
    }

    /**
     * Processes media data for a given image attribute and item data.
     *
     * */
    public function processMedia(string $imageAttr, array $itemData, array &$imageAttrCode, array &$updateMedia, array $medias, $assetPath = false): array
    {
        $mappingImage = $this->checkMappingInDbForImage($imageAttr, 'productImage', $itemData['sku']);
        if ($assetPath) {
            $fullUrl = route('admin.dam.file.fetch', ['path' => $assetPath]);
        } else {
            $urlPath = is_array($itemData[$imageAttr] ?? null) ? ($itemData[$imageAttr][0] ?? null) : ($itemData[$imageAttr] ?? null);
            $fullUrl = Storage::url($urlPath);
        }

        if (! empty($mappingImage)) {
            $updateMedia[] = [
                'alt'                => 'Some more alt text',
                'id'                 => $mappingImage[0]['externalId'],
                'previewImageSource' => $fullUrl,
            ];

            return $medias;
        }

        if (! in_array($imageAttr, $imageAttrCode)) {
            $imageAttrCode[] = $imageAttr;
        }

        $medias[$itemData['sku']][] = [
            'mediaContentType' => 'IMAGE',
            'originalSource'   => $fullUrl,
        ];

        return $medias;
    }

    public function processGallery(string $imageAttr, array $itemData, array &$imageAttrCode, array &$updateMedia, array $medias): array
    {
        $imageData = $itemData[$imageAttr];

        if (is_string($imageData)) {
            $imageData = [$imageData];
        }
        foreach ($imageData as $key => $image) {
            $image = str_replace(' ', '%20', $image);
            $galleryImageAttribute = $imageAttr.'_'.$key;
            $fullUrl = Storage::url($image);
            $mappingImage = $this->checkMappingInDbForImage($galleryImageAttribute, 'productImage', $itemData['sku']);
            if (! empty($mappingImage)) {
                $updateMedia[] = [
                    'id'                 => $mappingImage[0]['externalId'],
                    'previewImageSource' => $fullUrl,
                    'referencesToAdd'    => [$mappingImage[0]['relatedId']],
                ];
            }
            if (empty($mappingImage)) {
                $imageAttrCode[] = $galleryImageAttribute;

                $medias[$itemData['sku']][] = [
                    'mediaContentType' => 'IMAGE',
                    'originalSource'   => $fullUrl,
                ];
            }
        }

        return $medias;
    }

    /**
     * Get Locale Specific Attributes
     */
    protected function getLocaleSpecificFields(array $data, $locale): array
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['locale_specific'][$locale] ?? [];
    }

    /**
     * Get Locale and channel Specific Attributes
     */
    public function getChannelLocaleSpecificFields(array $data, string $channel, string $locale): array
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_locale_specific'][$channel][$locale] ?? [];
    }

    /**
     * Get channel Specific Attributes
     */
    protected function getChannelSpecificFields(array $data, $channel): array
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_specific'][$channel] ?? [];
    }

    /**
     * Get Common Attributes
     */
    protected function getCommonFields(array $data): array
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('common', $data['values'])
        ) {
            return [];
        }

        return $data['values']['common'];
    }
}
