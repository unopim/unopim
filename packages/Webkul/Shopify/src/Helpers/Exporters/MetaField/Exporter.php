<?php

namespace Webkul\Shopify\Helpers\Exporters\MetaField;

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Shopify\Exceptions\InvalidCredential;
use Webkul\Shopify\Exceptions\InvalidLocale;
use Webkul\Shopify\Helpers\ShoifyMetaFieldType;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyMetaFieldRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;
use Webkul\Shopify\Traits\TranslationTrait;

class Exporter extends AbstractExporter
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;
    use TranslationTrait;

    public const BATCH_SIZE = 10;

    public const NOT_FOUND_DEFINITION = 'Definition not found.';

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

    /**
     * Default locale of shopify store
     */
    protected $shopifyDefaultLocale;

    /**
     * Shopify metafield type data.
     */
    protected $shoifyMetaFieldTypeData;

    protected bool $exportsFile = false;

    /**
     * Create a new instance of the exporter.
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected ShopifyCredentialRepository $shopifyRepository,
        protected ShoifyMetaFieldType $shoifyMetaFieldType,
        protected ShopifyMetaFieldRepository $shopifyMetaFieldRepository
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the channels and locales for the export process.
     *
     * @return void
     */
    public function initialize()
    {
        $this->initCredential();

        $this->initDefaultLocale();
    }

    /**
     * Initialize credentials data from filters
     */
    protected function initCredential(): void
    {
        $filters = $this->getFilters();
        $this->shoifyMetaFieldTypeData = $this->shoifyMetaFieldType->getMetaFieldTypeInShopify();
        $this->credential = $this->shopifyRepository->find($filters['credentials']);

        if (! $this->credential?->active) {
            $this->jobLogger->warning(trans('shopify::app.shopify.export.errors.invalid-credential'));

            $this->export->state = ExportHelper::STATE_FAILED;
            $this->export->errors = [trans('shopify::app.shopify.export.errors.invalid-credential')];
            $this->export->save();

            throw new InvalidCredential;
        }

        $this->credentialArray = [
            'shopUrl'     => $this->credential->shopUrl,
            'accessToken' => $this->credential->accessToken,
            'apiVersion'  => $this->credential->apiVersion,
        ];
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

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('shopify.metafield.export.before', $batch);

        $this->initialize();

        $this->prepareMetafieldShopify($batch, $filePath);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, ExportHelper::STATE_PROCESSED);

        Event::dispatch('shopify.metafield.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        return $this->source->orderBy('id', 'desc')->all()?->getIterator();
    }

    public function prepareMetafieldShopify(JobTrackBatchContract $batch, mixed $filePath)
    {
        foreach ($batch->data as $rawData) {
            $shopUrl = $this->credentialArray['shopUrl'];
            $id = $this->extractId($rawData['apiUrl'] ?? null, $shopUrl);
            $formattedData = $this->prepareMetaFieldDefinition($rawData, $id);
            $responseData = $this->apiRequestShopify($formattedData, $id);

            if (! empty($responseData['body']['errors'])) {
                $this->handleErrors($responseData['body']['errors'], $rawData['code']);

                continue;
            }
            if ($id) {
                $resultCollection = $responseData['body']['data']['metafieldDefinitionUpdate'] ?? [];
                if (! empty($resultCollection['userErrors'])) {
                    $errorsMessage = array_column($resultCollection['userErrors'], 'message');
                    if (in_array(self::NOT_FOUND_DEFINITION, $errorsMessage)) {
                        $this->processCreateFlow($rawData, $shopUrl);

                        continue;
                    }
                    $this->handleErrors($resultCollection['userErrors'], $rawData['code']);

                    continue;
                }
                $this->createdItemsCount++;
            } else {
                $this->createMetafieldDefinitionMapping($responseData, $rawData, $shopUrl);
            }
        }
    }

    private function extractId(?string $apiUrl, string $shopUrl): ?string
    {
        if (! $apiUrl) {
            return null;
        }
        $metaFieldInApi = json_decode($apiUrl, true);

        return $metaFieldInApi[$shopUrl] ?? null;
    }

    private function processCreateFlow(array $rawData, string $shopUrl, $id = null): void
    {
        $formattedData = $this->prepareMetaFieldDefinition($rawData, $id);
        $responseData = $this->apiRequestShopify($formattedData, $id);
        $this->createMetafieldDefinitionMapping($responseData, $rawData, $shopUrl);
    }

    public function createMetafieldDefinitionMapping(array $responseData, array $rawData, string $shopUrl): void
    {
        $resultCollection = $responseData['body']['data']['metafieldDefinitionCreate'] ?? [];
        $userErrors = $resultCollection['userErrors'] ?? [];

        if (! empty($responseData['body']['errors'])) {

            $this->handleErrors($responseData['body']['errors'], $rawData['code']);

            return;
        }

        if (! empty($userErrors)) {
            if ($userErrors[0]['code'] === 'TAKEN') {
                $formattedData = $this->prepareMetaFieldDefinition($rawData, true);
                $response = $this->requestGraphQlApiAction('metafieldDefinitionUpdate', $this->credentialArray, ['input' => $formattedData]);
                $metaDataResponse = $response['body']['data']['metafieldDefinitionUpdate'] ?? [];
                if (! empty($metaDataResponse['userErrors'])) {
                    $this->handleErrors($metaDataResponse['userErrors'], $rawData['code']);

                    return;
                }
                $metaId = $response['body']['data']['metafieldDefinitionUpdate']['updatedDefinition']['id'] ?? null;
            } else {
                $this->handleErrors($userErrors, $rawData['code']);

                return;
            }
        } else {
            $metaId = $resultCollection['createdDefinition']['id'] ?? null;
        }

        if ($metaId) {
            $apiUrlData = [$shopUrl => $metaId];

            $this->shopifyMetaFieldRepository->update(
                ['apiUrl' => json_encode($apiUrlData, true)],
                $rawData['id']
            );

            $this->createdItemsCount++;
        }
    }

    private function handleErrors(array $errors, $code): void
    {
        $this->logWarning($errors, $code);
        $this->skippedItemsCount++;
    }

    /**
     * Prepare meta field definition
     */
    public function prepareMetaFieldDefinition($rowData, $id = null): array
    {
        $formattedData = [
            'access' => [
                'storefront' => $rowData['storefronts'] ? 'PUBLIC_READ' : 'NONE',
            ],
            'ownerType'  => $rowData['ownerType'] == 'PRODUCT' ? 'PRODUCT' : 'PRODUCTVARIANT',
        ];

        if (! empty($rowData['name_space_key'])) {
            $nameSapceAndKey = explode('.', $rowData['name_space_key']);
            $formattedData['namespace'] = $nameSapceAndKey[0];
            $formattedData['key'] = $nameSapceAndKey[1];
        }
        $type = $rowData['type'] ?? null;
        if ($type && isset($rowData['listvalue'])) {
            $type = $rowData['listvalue'] ? 'list.'.$type : $type;
        }

        if (! $id) {
            $formattedData['type'] = $type;
        }

        if (! empty($rowData['validations'])) {
            $validations = [];
            $validationDatas = json_decode($rowData['validations'], true);
            $maxunit = $validationDatas['maxunit'] ?? null;
            $minunit = $validationDatas['minunit'] ?? null;
            unset($validationDatas['maxunit'], $validationDatas['minunit']);
            foreach ($validationDatas as $key => $validationData) {
                if ($validationData == null) {
                    continue;
                }
                if ($maxunit && $key == 'max') {
                    $validationData = json_encode([
                        'value' => $validationData,
                        'unit'  => $maxunit,
                    ], true);
                } elseif ($minunit && $key == 'min') {
                    $validationData = json_encode([
                        'value' => $validationData,
                        'unit'  => $minunit,
                    ], true);
                }
                $key = in_array($type, ['list.rating', 'rating']) ? 'scale_'.$key : $key;

                $validations[] = [
                    'name'  => $key,
                    'value' => $validationData ?? 0,
                ];
            }

            $formattedData['validations'] = $validations;
        }

        $formattedData['name'] = $rowData['attribute'] ?? '';
        $formattedData['description'] = $rowData['description'] ?? '';
        $formattedData['pin'] = (bool) $rowData['pin'];

        if (! empty($rowData['options'])) {
            $options = json_decode($rowData['options'], true);
            $capabilities = [];
            foreach ($options as $key => $option) {
                if (isset($this->shoifyMetaFieldTypeData[$rowData['type']][$key])) {
                    $capabilities[$key] = [
                        'enabled' => (bool) $option,
                    ];
                }
            }

            $formattedData['capabilities'] = $capabilities;
        }

        return $formattedData;
    }

    /**
     * Make an API request to Shopify to create or update a category.
     */
    public function apiRequestShopify($metaFieldFormattedData, $id = null)
    {
        $mutationType = $id ? 'metafieldDefinitionUpdate' : 'metafieldDefinitionCreate';

        $response = $this->requestGraphQlApiAction($mutationType, $this->credentialArray, ['input' => $metaFieldFormattedData]);

        return $response;
    }

    /**
     * log Warning generate
     */
    public function logWarning(array $data, string $code): void
    {
        if (! empty($data) && ! empty($code)) {
            $error = json_encode($data, true);

            $this->jobLogger->warning(
                "Warning for MetaField with attribute code: {$code}, : {$error}"
            );
        }
    }

    /**
     * Get locale-specific fields from the raw data.
     */
    private function getLocaleSpecificFields(array $data, ?string $locale): array
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('additional_data', $data) || ! array_key_exists('locale_specific', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['locale_specific'][$locale] ?? [];
    }
}
