<?php

namespace Webkul\Shopify\Helpers\Importers\Product;

use Illuminate\Support\Facades\Http;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Helpers\Importers\Product\SKUStorage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shopify\Helpers\ShoifyMetaFieldType;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyExportMappingRepository;
use Webkul\Shopify\Repositories\ShopifyMappingRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;
use Webkul\Shopify\Traits\ValidatedBatched;

class Importer extends AbstractImporter
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;
    use ValidatedBatched;

    public const BATCH_SIZE = 50;

    public const UNOPIM_ENTITY_NAME = 'product';

    /**
     * Cached attribute families
     */
    protected mixed $attributeFamilies = [];

    /**
     * Cached attributes
     */
    protected mixed $attributes = [];

    /**
     * Cached categories
     */
    protected array $categories = [];

    /**
     * All channels selected currency codes
     */
    protected array $currencies = [];

    /**
     * Channel code as key and locale codes in array as value
     */
    protected array $channelsAndLocales = [];

    /**
     * all child in unopim
     */
    protected array $allChildInUnopim = [];

    /**
     * Shopify credential.
     *
     * @var mixed
     */
    protected $credential;

    /**
     * job locale
     */
    private $locale;

    /**
     * job status
     */
    private $update = false;

    private $updateVarint;

    /**
     * job channel code
     */
    private $channel;

    /**
     * job currency code
     */
    private $currency;

    protected $importMapping;

    protected $defintiionMapping;

    /**
     * Shopify credential as array for api request.
     *
     * @var mixed
     */
    protected $credentialArray;

    protected $exportMapping;

    /**
     * Shopify metafield type data.
     */
    protected $shoifyMetaFieldTypeData;

    /**
     * Valid csv columns
     */
    protected array $validColumnNames = [
        'locale',
        'channel',
        'type',
        'attribute_family',
        'parent',
        'categories',
        'related_products',
        'cross_sells',
        'up_sells',
        'configurable_attributes',
        'associated_skus',
    ];

    protected $productIndexes = ['title', 'handle', 'vendor', 'descriptionHtml', 'productType', 'tags'];

    protected $seoFields = ['metafields_global_title_tag', 'metafields_global_description_tag'];

    protected $variantIndexes = ['inventoryPolicy', 'barcode', 'taxable', 'compareAtPrice', 'sku', 'inventoryTracked', 'cost', 'weight', 'price', 'inventoryQuantity'];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductRepository $productRepository,
        protected SKUStorage $skuStorage,
        protected ChannelRepository $channelRepository,
        protected FieldProcessor $fieldProcessor,
        protected ShopifyCredentialRepository $shopifyRepository,
        protected ShopifyExportMappingRepository $shopifyExportmapping,
        protected FileStorer $fileStorer,
        protected CategoryRepository $categoryRepository,
        protected ShopifyMappingRepository $shopifyMappingRepository,
        protected ShoifyMetaFieldType $shoifyMetaFieldType,
    ) {
        parent::__construct($importBatchRepository);

        $this->initAttributes();
    }

    /**
     * Load all attributes and families to use later
     */
    protected function initAttributes(): void
    {
        $this->attributeFamilies = $this->attributeFamilyRepository->all();

        $this->attributes = $this->attributeRepository->all()->keyBy('code');

        $this->importMapping = $this->shopifyExportmapping->find(3);

        $this->initializeChannels();

        foreach ($this->attributes as $key => $attribute) {
            if ($attribute->type === 'price') {
                $this->addPriceAttributesColumns($attribute->code);

                continue;
            }

            $this->validColumnNames[] = $attribute->code;
        }
    }

    /**
     * initialize channels, locales and currecies value
     */
    protected function initializeChannels(): void
    {
        $this->exportMapping = $this->shopifyExportmapping->find(3);

        $channels = $this->channelRepository->all();

        foreach ($channels as $channel) {
            $this->channelsAndLocales[$channel->code] = $channel->locales?->pluck('code')?->toArray() ?? [];

            $this->currencies = array_merge($this->currencies, $channel->currencies?->pluck('code')?->toArray() ?? []);
        }
    }

    /**
     * Add valid column names for the price attribute according to currencies
     */
    public function addPriceAttributesColumns(string $attributeCode): void
    {
        foreach ($this->currencies as $currency) {
            $this->validColumnNames[] = $this->getPriceTypeColumnName($attributeCode, $currency);
        }
    }

    /**
     * Get formatted price column name
     */
    protected function getPriceTypeColumnName(string $attributeCode, string $currency): string
    {
        return "{$attributeCode} ({$currency})";
    }

    public function validateData(): void
    {
        $this->saveValidatedBatches();
    }

    /**
     * Initialize Filters
     */
    protected function initFilters(): void
    {
        $filters = $this->import->jobInstance->filters;

        $this->shoifyMetaFieldTypeData = $this->shoifyMetaFieldType->getMetaFieldTypeInShopify();

        $this->credential = $this->shopifyRepository->find($filters['credentials'] ?? null);
        if (! $this->credential?->active) {
            throw new \InvalidArgumentException('Invalid Credential: The credential is either disabled, incorrect, or does not exist');
        }
        $this->locale = $filters['locale'] ?? null;

        $this->channel = $filters['channel'] ?? null;

        $this->currency = $filters['currency'] ?? null;

        $this->defintiionMapping = array_merge(array_keys($this->credential?->extras['productMetafield'] ?? []), array_keys($this->credential?->extras['productVariantMetafield'] ?? []));
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
            throw new \InvalidArgumentException('Disabled Shopify credential');
        }

        $this->credentialArray = [
            'shopUrl'     => $this->credential?->shopUrl,
            'accessToken' => $this->credential?->accessToken,
            'apiVersion'  => $this->credential?->apiVersion,
        ];

        $products = new \Webkul\Shopify\Helpers\Iterator\ProductIterator($this->credentialArray);

        return $products;
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        return true;
    }

    /**
     * Start the import process for Category Import
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->saveProductsData($batch);

        return true;
    }

    /**
     * Save products from current batch
     */
    protected function saveProductsData(JobTrackBatchContract $batch): bool
    {
        $this->initFilters();
        foreach ($batch->data as $rowData) {
            $productId = $rowData['node']['id'];
            $isRemainingVariant = $rowData['node']['variants']['pageInfo']['hasNextPage'] ?? false;
            $variants = $rowData['node']['variants']['edges'];
            if ($isRemainingVariant) {
                $lastVariant = end($rowData['node']['variants']['edges']);
                $cursorVariant = $lastVariant['cursor'];
                $variables = [
                    'productId' => $productId,
                    'after'     => $cursorVariant,
                ];

                $this->credentialArray = [
                    'shopUrl'     => $this->credential?->shopUrl,
                    'accessToken' => $this->credential?->accessToken,
                    'apiVersion'  => $this->credential?->apiVersion,
                ];

                $data = $this->requestGraphQlApiAction('gettingRemaingVariant', $this->credentialArray, $variables);
                $remainData = $data['body']['data']['product']['variants']['edges'];
                $variants = array_merge($variants, $remainData);
            }

            $unopimCategory = $this->getCollectionFromShopify($rowData['node']['collections']['edges'] ?? []);
            $productMedias = $rowData['node']['media']['nodes'];
            $mediaData = array_filter($productMedias, fn ($item) => $item['__typename'] === 'MediaImage');
            $imageMediaids = array_column($mediaData, 'id') ?? [];
            $image = [];
            $image = array_map(function ($item) {
                return $item['image']['url'];
            }, $mediaData);
            $count = 0;
            $count = count(array_filter($rowData['node']['options'], fn ($option) => $option['name'] !== 'Title' || ! in_array('Default Title', $option['values'])));

            $mappingAttr = $this->importMapping->mapping['shopify_connector_settings'] ?? [];
            $mediaMapping = $this->importMapping->mapping['mediaMapping'] ?? [];
            $simpleProductFamilyId = $mappingAttr['family_variant'] ?? null;

            if (! $simpleProductFamilyId) {
                continue;
            }

            $metaFieldAllAttr = $this->defintiionMapping ?? [];

            unset($mappingAttr['family_variant']);
            $extractProductAttr = array_intersect_key($mappingAttr, array_flip($this->productIndexes));
            $extractSeoAttr = array_intersect_key($mappingAttr, array_flip($this->seoFields));
            $extractVariantAttr = array_intersect_key($mappingAttr, array_flip($this->variantIndexes));
            $common = [];
            $channelSpecific = [];
            $localeSpecific = [];
            $channelAndLocaleSpecific = [];
            $productModelattribiteUnopim = $this->mapAttributes($extractProductAttr, $rowData, false);

            $seoAttrUnopim = $this->mapAttributes($extractSeoAttr, $rowData, true);

            if (! $productModelattribiteUnopim || ! $seoAttrUnopim) {
                continue;
            }

            [$productCommon, $productLocaleSpecific, $productChannelSpecific, $productChannelAndLocaleSpecific] = $productModelattribiteUnopim;

            [$seoCommon, $seoLocaleSpecific, $seoChannelSpecific, $seoChannelAndLocaleSpecific] = $seoAttrUnopim;

            [$metaFieldCommon, $metaFieldLocaleSpecific, $metaFieldChannelSpecific, $metaFieldChannelAndLocaleSpecific] = $this->mapMetafieldsAttribute($rowData['node']['metafields']['edges'] ?? [], $metaFieldAllAttr);

            $common = array_merge($productCommon, $seoCommon, $metaFieldCommon);
            $common['status'] = $rowData['node']['status'] == 'ACTIVE' ? 'true' : 'false';
            $localeSpecific = array_merge($productLocaleSpecific, $seoLocaleSpecific, $metaFieldLocaleSpecific);
            $channelSpecific = array_merge($productChannelSpecific, $seoChannelSpecific, $metaFieldChannelSpecific);
            $channelAndLocaleSpecific = array_merge($productChannelAndLocaleSpecific, $seoChannelAndLocaleSpecific, $metaFieldChannelAndLocaleSpecific);
            $this->requestJobLocaleAndChannel();
            if ($count > 0) {
                $parentData = $this->processConfigurableProduct(
                    $rowData,
                    $simpleProductFamilyId,
                    $unopimCategory,
                    $variants,
                    $image,
                    $imageMediaids,
                    $common,
                    $localeSpecific,
                    $channelSpecific,
                    $channelAndLocaleSpecific,
                    $mediaMapping,
                    $extractVariantAttr,
                    $metaFieldAllAttr
                );
                if (! $parentData) {
                    continue;
                }

            } else {
                $childData = $this->processSimpleProduct(
                    $rowData,
                    $simpleProductFamilyId,
                    $unopimCategory,
                    $variants,
                    $image,
                    $imageMediaids,
                    $common,
                    $localeSpecific,
                    $channelSpecific,
                    $channelAndLocaleSpecific,
                    $mediaMapping,
                    $extractVariantAttr,
                    $metaFieldCommon,
                    $metaFieldChannelSpecific,
                    $metaFieldLocaleSpecific,
                    $metaFieldChannelAndLocaleSpecific
                );

                if (! $childData) {
                    continue;
                }
            }

            if ($this->update) {
                $this->updatedItemsCount++;
            } else {
                $this->createdItemsCount++;
            }
        }

        $this->updateBatchtate($batch);

        return true;
    }

    /**
     * process configurable product
     */
    public function processConfigurableProduct(
        $rowData,
        $simpleProductFamilyId,
        $unopimCategory,
        $variants,
        $image,
        $imageMediaids,
        $common,
        $localeSpecific,
        $channelSpecific,
        $channelAndLocaleSpecific,
        $mediaMapping,
        $extractVariantAttr,
        $metaFieldAllAttr
    ) {
        $attributes = [];
        $storeForVariant = [];
        $attributes = $this->validateAttributes($rowData['node']['options']);
        if ($attributes === null) {
            return null;
        }
        $family_code = $simpleProductFamilyId;

        $familyModel = $this->attributeFamilyRepository->where('id', $family_code)->first();

        if (! $familyModel) {
            $this->jobLogger->warning('family not exist for the title:- ['.$rowData['node']['title'].'] 1st you need to import family');

            return null;
        }

        $configurableAttributes = [];

        foreach ($familyModel?->getConfigurableAttributes() ?? [] as $attribute) {
            $configurableAttributes[] = [
                'code' => $attribute->code,
                'name' => $attribute->name,
                'id'   => $attribute->id,
            ];
        }

        if (empty($configurableAttributes)) {
            return null;
        }

        $shopifyProductId = $rowData['node']['id'];
        $configProductMapping = $this->checkMappingInDb(['code' => $rowData['node']['handle']]);
        $parentSkuFromUnopim = null;
        $configId = $this->processConfigurableProductData(
            $rowData,
            $familyModel,
            $attributes,
            $parentSkuFromUnopim,
        );

        if ($configId === null) {
            return null;
        }

        if (! $configProductMapping) {
            $this->parentMapping($rowData['node']['handle'], $shopifyProductId, $this->import->id);
        }

        $allMediaIdVariants = [];
        $variantProductData = $this->processVariants(
            $variants,
            $rowData,
            $shopifyProductId,
            $configId,
            $extractVariantAttr,
            $mediaMapping,
            $metaFieldAllAttr,
            $allMediaIdVariants,
        );

        $mappedImageAttr = null;

        if (!empty($mediaMapping)) {
            $title  = $rowData['node']['title']  ?? '';
            $handle = $rowData['node']['handle'] ?? '';
            $id     = $rowData['node']['id']     ?? null;

            if ($mediaMapping['mediaType'] === 'image') {
                $mappedImageAttr = $this->processMappedImages($mediaMapping, $image, $configId, $storeForVariant, $title, $imageMediaids, $handle, $id, $allMediaIdVariants);
            } elseif ($mediaMapping['mediaType'] === 'gallery') {
                $mappedImageAttr = $this->processMappedGallery($mediaMapping, $image, $configId, $storeForVariant, $title, $imageMediaids, $handle, $id, $allMediaIdVariants);
            }
        }

        if (!is_array($mappedImageAttr)) {
            return null;
        }

        [$mcommon, $mlocale_specific, $mchannel_specific, $mchannelAndLocaleSpecific] = $mappedImageAttr;

        $dataToUpdate = [
            'sku'     => $parentSkuFromUnopim ?? $rowData['node']['handle'],
            'status'  => $rowData['node']['status'] == 'ACTIVE' ? 1 : 0,
            'channel' => $this->channel,
            'locale'  => $this->locale,
            'values'  => [
                'common'           => array_merge($common, $mcommon ?? []),
                'channel_specific' => [
                    $this->channel => array_merge($channelSpecific, $mchannel_specific ?? [] ),
                ],

                'locale_specific'  => [
                    $this->locale => array_merge($localeSpecific, $mlocale_specific ?? []),
                ],

                'channel_locale_specific' => [
                    $this->channel => [
                        $this->locale => array_merge($channelAndLocaleSpecific, $mchannelAndLocaleSpecific ?? []),
                    ],
                ],
            ],
            'variants'   => $variantProductData,
            'categories' => $unopimCategory,
        ];

        $product = $this->productRepository->update($dataToUpdate, $configId);
        $allVariant = $product->variants?->toArray();
        $ids = array_column($allVariant, 'id');
        $skus = array_column($allVariant, 'sku');
        $formattedArray = array_combine($skus, $ids);
        $variantProductData = array_values($variantProductData);
        foreach ($product->variants->toArray() as $key => $svariant) {
            $variantData = $variantProductData[$key] ?? $variantProductData[$svariant['id']] ?? null;
            if (! $variantData) {
                continue;
            }

            $sku = $variantData['sku'] ?? null;
            if (! $sku || ! isset($formattedArray[$sku])) {
                $this->jobLogger->warning(sprintf('%s variant SKU not found in parent product %s', $sku, $product->sku));

                continue;
            }

            $product = $this->productRepository->update($variantData, $formattedArray[$sku]);
        }

        return true;
    }

    private function processVariants(
        array $variants,
        array $rowData,
        string $shopifyProductId,
        int $configId,
        array $extractVariantAttr,
        array $mediaMapping,
        array $metaFieldAllAttr,
        &$allMediaIdVariants,
    ) {
        $variantSkus = [];
        $variantProductData = [];
        $mcommon = [];
        $mlocale_specific = [];
        $mchannel_specific = [];
        $mchannelAndLocaleSpecific = [];
        foreach ($variants as $key => $productVariant) {
            $vsku = $productVariant['node']['sku'] ?? null;
            if (empty($vsku)) {
                $this->jobLogger->warning('Variant SKU not found in product '.$shopifyProductId);

                continue;
            }
            $vsku = str_replace(["\r", "\n"], '', $vsku);
            if (in_array($vsku, $variantSkus)) {
                $this->jobLogger->warning($vsku.':- Duplicate SKU Found in product');

                continue;
            }
            $variantSkus[] = $vsku;
            $variantMapping = $this->checkMappingInDb(['code' => $vsku]);
            if (! $variantMapping) {
                $this->parentMapping($vsku, $productVariant['node']['id'], $this->import->id, $shopifyProductId);
            }

            $variantProductExist = $this->productRepository->findOneByField('sku', $vsku);
            $imageValue = null;

            $variantImageAttr = $mediaMapping['mediaAttributes'] ?? null;

            if (is_string($variantImageAttr)) {
                $variantImageAttr = explode(',', $variantImageAttr);
            }
            if (is_array($variantImageAttr)) {
                $variantImageAttr = $variantImageAttr[0];
            }
            $mappingAttr = $variantImageAttr ?? null;
            $mType = $mediaMapping['mediaType'] ?? null;
            if ($mType == 'gallery') {
                $mappingAttr = $variantImageAttr.'_0';
            }
            $imageUrl = $productVariant['node']['media']['nodes'][0]['image']['url'] ?? [];
            if (! empty($imageUrl)) {
                $mediaId = $productVariant['node']['media']['nodes'][0]['id'];
                $variantImage = $variantProductExist->id ?? $configId;
                if ($variantImageAttr) {
                    $imagePath = 'product'.DIRECTORY_SEPARATOR.$variantImage.DIRECTORY_SEPARATOR.$variantImageAttr.DIRECTORY_SEPARATOR;
                    $imageValue = $this->handleUrlField($imageUrl, $imagePath);
                    $mappingMedia = $this->checkMappingInDbForImage($mappingAttr, 'productImage', $vsku);
                    $allMediaIdVariants[] = $mediaId;
                    if (empty($mappingMedia)) {
                        $this->imageMapping('productImage', $mappingAttr, $mediaId, $this->import->id, $shopifyProductId, $vsku);
                    }
                }
            }

            $variantProductValue = $this->formatVariantData($productVariant, $extractVariantAttr);
            if (! $variantProductValue) {
                continue;
            }

            [$vcommon, $vlocale_specific, $vchannel_specific, $vchannelAndLocaleSpecific] = $variantProductValue;
            if ($variantImageAttr) {
                $vimage = $variantImageAttr;
                $vcommon[$vimage] = isset($mcommon[$vimage]) ? '' : ($vlocale_specific[$vimage] ?? null);
                $vlocale_specific[$vimage] = isset($mlocale_specific[$vimage]) ? '' : ($vlocale_specific[$vimage] ?? null);
                $vchannel_specific[$vimage] = isset($mchannel_specific[$vimage]) ? '' : ($vchannel_specific[$vimage] ?? null);
                $vchannelAndLocaleSpecific[$vimage] = isset($mchannelAndLocaleSpecific[$vimage]) ? '' : ($vchannelAndLocaleSpecific[$vimage] ?? null);
            }

            if ($variantImageAttr) {

                $vImageAttribute = $this->attributes[$variantImageAttr] ?? null;
                if ($vImageAttribute->toArray()['type'] === 'gallery' && $imageValue) {
                    $imageValue = explode(',', $imageValue);
                }

                if ($vImageAttribute->is_required && empty($imageValue)) {
                    $this->jobLogger->warning($variantImageAttr.':- Field Is required for Sku:-'.$vsku);

                    continue;
                }
                if (! $vImageAttribute?->value_per_locale && ! $vImageAttribute?->value_per_channel) {
                    $vcommon[$variantImageAttr] = $imageValue;
                }

                if ($vImageAttribute?->value_per_locale && ! $vImageAttribute?->value_per_channel) {
                    $vlocale_specific[$variantImageAttr] = $imageValue;
                }

                if (! $vImageAttribute?->value_per_locale && $vImageAttribute?->value_per_channel) {
                    $vchannel_specific[$variantImageAttr] = $imageValue;
                }

                if ($vImageAttribute?->value_per_locale && $vImageAttribute?->value_per_channel) {
                    $vchannelAndLocaleSpecific[$variantImageAttr] = $imageValue;
                }
            }

            [$vMdcommon, $vMdlocale_specific, $vMdchannel_specific, $vMdchannelAndLocaleSpecific] = $this->mapMetafieldsAttribute($productVariant['node']['metafields']['edges'] ?? [], $metaFieldAllAttr);
            $vkey = $variantProductExist ? $variantProductExist->id : 'variant_'.$key;
            if ($this->updateVarint) {
                $this->updatedItemsCount++;
            } else {
                $this->createdItemsCount++;
            }

            $variantProductData[$vkey] = [
                'sku'    => $vsku ?? '',
                'status' => $rowData['node']['status'] == 'ACTIVE' ? 1 : 0,
                'values' => [
                    'common'           => array_merge($vcommon, $vMdcommon),
                    'channel_specific' => [
                        $this->channel => array_merge($vchannel_specific, $vMdchannel_specific),
                    ],
                    'locale_specific'  => [
                        $this->locale => array_merge($vlocale_specific, $vMdlocale_specific),
                    ],
                    'channel_locale_specific' => [
                        $this->channel => [
                            $this->locale => array_merge($vchannelAndLocaleSpecific, $vMdchannelAndLocaleSpecific),
                        ],
                    ],
                ],
            ];
        }

        $leftChildProduct = array_diff(array_column($this->allChildInUnopim, 'id'), array_keys($variantProductData));
        if (! empty($leftChildProduct)) {
            $this->addExistingVariantProduct($leftChildProduct, $variantProductData);
        }

        return $variantProductData;
    }

    private function addExistingVariantProduct($leftChildProduct, &$variantProductData): void
    {
        foreach ($leftChildProduct ?? [] as $key => $productIds) {
            $variantProductData[$productIds] = [
                'sku'    => $this->allChildInUnopim[$key]['sku'],
                'status' => $this->allChildInUnopim[$key]['status'],
                'values' => $this->allChildInUnopim[$key]['values'],
            ];
        }
    }

    private function processConfigurableProductData($rowData, $familyModel, $attributes, &$parentSkuFromUnopim)
    {
        $variantSku = $rowData['node']['variants']['edges'][0]['node']['sku'];
        $variantData = $this->productRepository->findOneByField('sku', $variantSku);
        if ($variantData?->parent?->sku) {
            $parentSkuFromUnopim = $variantData?->parent?->sku;
            $configProductExist = $this->productRepository->findOneByField('sku', $variantData?->parent?->sku);
        } else {
            $parentSkuFromUnopim = $rowData['node']['handle'];
            $configProductExist = $this->productRepository->findOneByField('sku', $rowData['node']['handle']);
        }
        $configId = $configProductExist?->id;
        $this->update = true;

        $this->updateVarint = true;
        $this->allChildInUnopim = $configProductExist?->variants?->toArray() ?? [];
        if (! $configProductExist) {

            if (! $familyModel) {
                $this->jobLogger->warning('family not mapping for the title:- ['.$rowData['node']['title'].']');

                return null;
            }
            $this->updateVarint = false;
            $data[$rowData['node']['handle']] = [
                'type'                => 'configurable',
                'sku'                 => $rowData['node']['handle'],
                'status'              => $rowData['node']['status'] == 'ACTIVE' ? 1 : 0,
                'attribute_family_id' => $familyModel->id,
                'super_attributes'    => $attributes,
            ];

            $createdConfigProduct = $this->productRepository->create($data[$rowData['node']['handle']]);
            $configId = $createdConfigProduct->id;
            $this->update = false;
        }

        return $configId;
    }

    /**
     * check attributes exist in unopim
     */
    private function validateAttributes($options)
    {
        $attributes = [];
        $attrNotExist = [];

        foreach ($options as $attr) {
            $attrCode = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($attr['name']));
            $vAttribute = $this->attributes[$attrCode] ?? null;

            if ($vAttribute) {
                $attributes[$attrCode] = $attrCode;
            } else {
                $attrNotExist[] = $attrCode;
            }
        }

        if (! empty($attrNotExist)) {
            $this->jobLogger->warning(json_encode($attrNotExist).' Attributes not exist for product.');

            return null;
        }

        return $attributes;
    }

    /**
     * process simple product
     */
    public function processSimpleProduct(
        $rowData,
        $simpleProductFamilyId,
        $unopimCategory,
        $variants,
        $image,
        $imageMediaids,
        $common,
        $localeSpecific,
        $channelSpecific,
        $channelAndLocaleSpecific,
        $mediaMapping,
        $extractVariantAttr,
        $metaFieldCommon,
        $metaFieldChannelSpecific,
        $metaFieldLocaleSpecific,
        $metaFieldChannelAndLocaleSpecific
    ) {
        $shopifyProductId = $rowData['node']['id'];
        $storeForVariant = [];
        foreach ($variants as $key => $productVariant) {
            $variantData = $this->formatVariantData($productVariant, $extractVariantAttr);
            if (empty($productVariant['node']['sku'])) {
                $this->jobLogger->warning('SKU not found in product '.$shopifyProductId);

                continue;
            }
        }

        if (! $variantData) {
            return false;
        }

        [$vcommon, $vlocale_specific, $vchannel_specific, $vchannelAndLocaleSpecific] = $variantData;

        if (empty($vcommon['sku'])) {
            $vcommon['sku'] = $rowData['node']['handle'];
        }

        $productExist = $this->productRepository->findOneByField('sku', $vcommon['sku']);
        $simpleId = $productExist?->id;
        $this->update = true;
        $variantSku = $productVariant['node']['sku'] ?? $rowData['node']['handle'];
        $simpleProductMapping = $this->checkMappingInDb(['code' => $variantSku]);

        if (! $simpleProductMapping) {
            $this->parentMapping($variantSku, $shopifyProductId, $this->import->id);
        }

        if (! $productExist) {
            $familyModel = $this->attributeFamilyRepository->where('id', $simpleProductFamilyId)->first();

            if (! $familyModel) {
                $this->jobLogger->warning('family not mapping for the title:- ['.$rowData['node']['title'].']');

                return false;
            }

            $data[$vcommon['sku']] = [
                'type'                => 'simple',
                'sku'                 => $vcommon['sku'],
                'status'              => $rowData['node']['status'] == 'ACTIVE' ? 1 : 0,
                'attribute_family_id' => $simpleProductFamilyId,
            ];
            $this->update = false;

            $createdProduct = $this->productRepository->create($data[$vcommon['sku']]);
            $simpleId = $createdProduct->id;
        }

        $mappedImageAttr = [
            [],
            [],
            [],
            [],
        ];

        if (! empty($mediaMapping) && $mediaMapping['mediaType'] === 'image') {
            $mappedImageAttr = $this->processMappedImages($mediaMapping, $image, $simpleId, $storeForVariant, $rowData['node']['title'] ?? '', $imageMediaids, $vcommon['sku'], $rowData['node']['id']);
        }

        if (! empty($mediaMapping) && $mediaMapping['mediaType'] === 'gallery') {
            $mappedImageAttr = $this->processMappedGallery($mediaMapping, $image, $simpleId, $storeForVariant, $rowData['node']['title'] ?? '', $imageMediaids, $vcommon['sku'], $rowData['node']['id']);
        }

        if (! $mappedImageAttr && ! empty($mediaMapping)) {
            return false;
        }

        [$mcommon, $mlocale_specific, $mchannel_specific, $mchannelAndLocaleSpecific] = $mappedImageAttr;

        $dataToUpdate = [
            'sku'     => $vcommon['sku'],
            'channel' => $this->channel,
            'status'  => $rowData['node']['status'] == 'ACTIVE' ? 1 : 0,
            'locale'  => $this->locale,
            'values'  => [
                'common'           => array_merge($common, $vcommon, $mcommon, $metaFieldCommon),
                'channel_specific' => [
                    $this->channel => array_merge($channelSpecific, $vchannel_specific, $mchannel_specific, $metaFieldChannelSpecific),
                ],
                'locale_specific'  => [
                    $this->locale => array_merge($localeSpecific, $vlocale_specific, $mlocale_specific, $metaFieldLocaleSpecific),
                ],
                'channel_locale_specific' => [
                    $this->channel => [
                        $this->locale => array_merge($channelAndLocaleSpecific, $vchannelAndLocaleSpecific, $mchannelAndLocaleSpecific, $metaFieldChannelAndLocaleSpecific),
                    ],
                ],
            ],
            'categories' => $unopimCategory,
        ];

        $product = $this->productRepository->update($dataToUpdate, $simpleId);

        return $product;
    }

    public function requestJobLocaleAndChannel()
    {
        request()->merge([
            'locale'  => $this->locale,
            'channel' => $this->channel,
        ]);
    }

    public function mapMetafieldsAttribute($shopifyMetaFiled, $metaFieldAllAttr): array
    {
        $common = [];
        $localeSpecific = [];
        $channelSpecific = [];
        $channelAndLocaleSpecific = [];
        foreach ($shopifyMetaFiled ?? [] as $metaData) {
            if (! in_array($metaData['node']['key'], $metaFieldAllAttr)) {
                continue;
            }
            $unoAttr = $metaData['node']['key'];
            $source = $metaData['node']['value'];

            $attribute = $this->attributes[$metaData['node']['key']] ?? null;
            if (! $attribute) {
                continue;
            }
            $unitOption = $this->shoifyMetaFieldTypeData[$metaData['node']['type']]['unitoptions'] ?? null;
            if ($unitOption) {
                $unitValue = json_decode($source, true);
                $source = $unitValue['value'] ?? 0;
            }

            if (! $attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $common[$unoAttr] = $source;
            }

            if ($attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $localeSpecific[$unoAttr] = $source;
            }

            if (! $attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelSpecific[$unoAttr] = $source;
            }

            if ($attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelAndLocaleSpecific[$unoAttr] = $source;
            }
        }

        return [
            $common,
            $localeSpecific,
            $channelSpecific,
            $channelAndLocaleSpecific,
        ];
    }

    public function updateBatchtate(JobTrackBatchContract $batch): void
    {
        $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
            ],
        ], $batch->id);
    }

    /*
    * Get collection code from shopify
    *
    */
    public function getCollectionFromShopify(array $collections): array
    {
        $collectionCode = [];

        foreach ($collections as $collection) {
            $categoryExist = $this->categoryRepository->where('code', $collection['node']['handle'])->first();
            if (! $categoryExist) {
                continue;
            }

            $collectionCode[] = $categoryExist?->code;
        }

        return $collectionCode;
    }

    /*
    * process image attributes
    */
    public function processMappedImages(array $mediaMapping, array $image, string $configId, array &$storeForVariant, string $title, $imageMediaids, $mappingSku, $productId): ?array
    {
        $common = [];
        $localeSpecific = [];
        $channelSpecific = [];
        $channelAndLocaleSpecific = [];
        $allMediaAttributes = $mediaMapping['mediaAttributes'] ?? [];
        if (! is_array($allMediaAttributes)) {
            $allMediaAttributes = explode(',', $allMediaAttributes);
        }

        foreach ($allMediaAttributes as $index => $mappedImageAttr) {
            $imgStore = '';
            $attribute = $this->attributes[$mappedImageAttr] ?? null;
            if (! empty($image[$index])) {
                if ($attribute?->is_required && empty($image[$index])) {
                    $this->jobLogger->warning($mappedImageAttr.':- Field Is required '.$title);

                    return null;
                }

                $imagePath = 'product'.DIRECTORY_SEPARATOR.$configId.DIRECTORY_SEPARATOR.$mappedImageAttr.DIRECTORY_SEPARATOR;
                $imgStore = $this->handleUrlField($image[$index], $imagePath);
                $mappingMedia = $this->checkMappingInDbForImage($mappedImageAttr, 'productImage', $mappingSku);
                if (empty($mappingMedia)) {
                    $this->imageMapping('productImage', $mappedImageAttr, $imageMediaids[$index], $this->import->id, $productId, $mappingSku);
                }
            }

            if (! $attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $common[$mappedImageAttr] = $imgStore;
            }

            if ($attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $localeSpecific[$mappedImageAttr] = $imgStore;
            }

            if (! $attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelSpecific[$mappedImageAttr] = $imgStore;
            }

            if ($attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelAndLocaleSpecific[$mappedImageAttr] = $imgStore;
            }
        }

        return [
            $common,
            $localeSpecific,
            $channelSpecific,
            $channelAndLocaleSpecific,
        ];
    }

    /*
    * process image attributes
    */
    public function processMappedGallery(array $mediaMapping, array $image, string $configId, array &$storeForVariant, string $title, $imageMediaids, $mappingSku, $productId, $allMediaIdVariants = []): ?array
    {
        $common = [];
        $localeSpecific = [];
        $channelSpecific = [];
        $channelAndLocaleSpecific = [];
        $allMediaAttributes = $mediaMapping['mediaAttributes'] ?? [];
        if (! is_array($allMediaAttributes)) {
            $allMediaAttributes = explode(',', $allMediaAttributes);
        }

        foreach ($allMediaAttributes as $index => $mappedImageAttr) {
            $imgStore = [];
            if (! isset($this->attributes[$mappedImageAttr])) {
                continue;
            }
            $attribute = $this->attributes[$mappedImageAttr];

            if ($attribute?->is_required && empty($image)) {
                $this->jobLogger->warning($mappedImageAttr.':- Field Is required '.$title);

                return null;
            }

            if (! empty($image)) {
                $init = 0;
                foreach ($image as $imageUrl) {
                    if (in_array($imageMediaids[$init], array_unique($allMediaIdVariants))) {
                        unset($imageMediaids[$init]);
                        $imageMediaids = array_values($imageMediaids);

                        continue;
                    }

                    $galleryAttr = $mappedImageAttr.'_'.$init;
                    $mappingMedia = $this->checkMappingInDbForImage($galleryAttr, 'productImage', $mappingSku);
                    if (empty($mappingMedia)) {
                        $this->imageMapping('productImage', $galleryAttr, $imageMediaids[$init], $this->import->id, $productId, $mappingSku);
                    }
                    $imagePath = 'product'.DIRECTORY_SEPARATOR.$configId.DIRECTORY_SEPARATOR.$mappedImageAttr.DIRECTORY_SEPARATOR;
                    $imgStore[] = $this->handleUrlField($imageUrl, $imagePath);
                    $init++;
                }
            }

            if (! $attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $common[$mappedImageAttr] = $imgStore;
            }

            if ($attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $localeSpecific[$mappedImageAttr] = $imgStore;
            }

            if (! $attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelSpecific[$mappedImageAttr] = $imgStore;
            }

            if ($attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelAndLocaleSpecific[$mappedImageAttr] = $imgStore;
            }
        }

        return [
            $common,
            $localeSpecific,
            $channelSpecific,
            $channelAndLocaleSpecific,
        ];
    }

    /**
     * Image Store
     */
    public function imageStorer(string $imageUrl): string
    {
        $fileName = explode('/', $imageUrl);
        $fileName = end($fileName);
        $fileName = explode('?', $fileName)[0];
        $localpath = '/tmp'.'/tmpstorage/'.$fileName;
        if (! file_exists(dirname($localpath))) {
            mkdir(dirname($localpath), 0755, true);
        }

        if (! is_writable(dirname($localpath))) {
            throw new \Exception(sprintf('%s must writable !!! ', dirname($localpath)));
        }

        $check = file_put_contents($localpath, $this->grabImage($imageUrl));

        return $localpath;
    }

    public function grabImage($url)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        // Block private/reserved IP ranges and non-HTTPS
        if (! $host || filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11',
        ])
            ->timeout(30)
            ->retry(3, 1000) // Retry up to 3 times with 1-second intervals
            ->get($url);

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    /**
     * Mapped attribute data for seo and product
     */
    public function mapAttributes($attributes, $rowData, $isSeo = false): ?array
    {
        $common = [];
        $localeSpecific = [];
        $channelSpecific = [];
        $channelAndLocaleSpecific = [];

        foreach ($attributes as $shopifyAttr => $unoAttr) {
            if (! isset($this->attributes[$unoAttr])) {
                continue;
            }
            $attribute = $this->attributes[$unoAttr];

            if ($isSeo) {
                if ($shopifyAttr == 'metafields_global_title_tag') {
                    $shopifyAttr = 'title';
                }

                if ($shopifyAttr == 'metafields_global_description_tag') {
                    $shopifyAttr = 'description';
                }
            }

            $source = $isSeo ? $rowData['node']['seo'][$shopifyAttr] : $rowData['node'][$shopifyAttr];

            if ($shopifyAttr == 'tags') {
                $source = implode(',', $source);
            }
            if ($attribute->is_required && empty($source)) {
                $this->jobLogger->warning($unoAttr.':- Field Is required For the title :-'.$rowData['node']['title']);

                return null;
            }

            if (! $attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $common[$unoAttr] = $source;
            }

            if ($attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $localeSpecific[$unoAttr] = $source;
            }

            if (! $attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelSpecific[$unoAttr] = $source;
            }

            if ($attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelAndLocaleSpecific[$unoAttr] = $source;
            }
        }

        return [
            $common,
            $localeSpecific,
            $channelSpecific,
            $channelAndLocaleSpecific,
        ];
    }

    /**
     * Variant Data formater
     */
    public function formatVariantData($variantData, $extractVariantAttr): ?array
    {
        // Initialize arrays to store different types of attributes
        $Opcommon = $Oplocale_specific = $Opchannel_specific = $OpchannelAndLocaleSpecific = [];
        $vcommon = $vlocale_specific = $vchannel_specific = $vchannelAndLocaleSpecific = [];

        // Helper function to classify attributes
        $classifyAttribute = function ($attribute, $name, $value, &$common, &$localeSpecific, &$channelSpecific, &$channelAndLocaleSpecific) {
            if (! $attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $common[$name] = $value;
            } elseif ($attribute?->value_per_locale && ! $attribute?->value_per_channel) {
                $localeSpecific[$name] = $value;
            } elseif (! $attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelSpecific[$name] = $value;
            } elseif ($attribute?->value_per_locale && $attribute?->value_per_channel) {
                $channelAndLocaleSpecific[$name] = $value;
            }
        };

        // Process selected options
        foreach ($variantData['node']['selectedOptions'] ?? [] as $option) {
            if ($option['name'] == 'Title' && $option['value'] == 'Default Title') {
                continue;
            }

            $name = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower($option['name']));
            if (! isset($this->attributes[$name])) {
                continue;
            }
            $attribute = $this->attributes[$name];

            $optionvalue = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $option['value']), '-');
            $optionForShopify = $attribute->options()->where('code', $optionvalue)?->get()?->first();

            if (! $optionForShopify) {
                $this->jobLogger->warning("{$option['name']} - {$option['value']}:- Option is not found in the unopim sku:- {$variantData['node']['sku']}");

                return null;
            }

            $classifyAttribute($attribute, $name, $optionForShopify?->code, $Opcommon, $Oplocale_specific, $Opchannel_specific, $OpchannelAndLocaleSpecific);
        }

        // Process extracted variant attributes
        foreach ($extractVariantAttr as $shopifyAttr => $unoAttr) {
            $value = null;
            if (! isset($this->attributes[$unoAttr])) {
                continue;
            }
            $attribute = $this->attributes[$unoAttr];
            switch ($shopifyAttr) {
                case 'cost':
                    $costPerItem = $variantData['node']['inventoryItem']['unitCost'];
                    $value[$this->currency] = $costPerItem ? (string) $costPerItem['amount'] : '0';
                    break;

                case 'price':
                    $value[$this->currency] = (string) ($variantData['node']['price'] ?? '0');
                    break;

                case 'weight':
                    $value = (string) ($variantData['node']['inventoryItem']['measurement']['weight']['value'] ?? '0');
                    break;

                case 'barcode':
                    $value = $variantData['node']['barcode'] ?? '';
                    if ($attribute->is_required && empty($value)) {
                        $this->jobLogger->warning("{$unoAttr}:- Unopim Field is required for the SKU :- {$variantData['node']['sku']}");

                        return null;
                    }

                    break;

                case 'taxable':
                    $value = $variantData['node']['taxable'] ? 'true' : 'false';
                    break;

                case 'compareAtPrice':
                    $value[$this->currency] = (string) ($variantData['node']['compareAtPrice'] ?? '0');
                    break;

                case 'inventoryPolicy':
                    $value = $variantData['node']['inventoryPolicy'] == 'CONTINUE' ? 'true' : 'false';
                    break;

                case 'inventoryTracked':
                    $value = $variantData['node']['inventoryItem']['tracked'] ? 'true' : 'false';
                    break;

                case 'inventoryQuantity':
                    $value = $variantData['node']['inventoryQuantity'];
                    break;
            }

            $classifyAttribute($attribute, $unoAttr, $value, $vcommon, $vlocale_specific, $vchannel_specific, $vchannelAndLocaleSpecific);
        }

        $vcommon['sku'] = str_replace(["\r", "\n"], '', $variantData['node']['sku']);

        // Return merged results
        return [
            array_merge($vcommon, $Opcommon),
            array_merge($vlocale_specific, $Oplocale_specific),
            array_merge($vchannel_specific, $Opchannel_specific),
            array_merge($vchannelAndLocaleSpecific, $OpchannelAndLocaleSpecific),
        ];
    }

    /**
     * Check if SKU exists
     */
    public function isSKUExist(string $sku): bool
    {
        return $this->skuStorage->has($sku);
    }
}
