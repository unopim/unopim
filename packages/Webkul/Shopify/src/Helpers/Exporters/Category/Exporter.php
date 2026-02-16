<?php

namespace Webkul\Shopify\Helpers\Exporters\Category;

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Shopify\Exceptions\InvalidCredential;
use Webkul\Shopify\Exceptions\InvalidLocale;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyMappingRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;
use Webkul\Shopify\Traits\TranslationTrait;

class Exporter extends AbstractExporter
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;
    use TranslationTrait;

    public const BATCH_SIZE = 10;

    public const COLLECTION_NOT_EXIST = 'Collection does not exist';

    /**
     * unopim entity name.
     *
     * @var string
     */
    public const UNOPIM_ENTITY_NAME = 'category';

    public const UPDATE_PUBLISH_CHANNEL = 'publishablePublish';

    public const UPDATE_UNPUBLISH_CHANNEL = 'unpublishableUnpublish';

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
     * Shopify sales channel publication ids
     */
    protected $publicationId = [];

    /**
     * Default locale of shopify store
     */
    protected $shopifyDefaultLocale;

    protected bool $exportsFile = false;

    /**
     * Create a new instance of the exporter.
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected ShopifyCredentialRepository $shopifyRepository,
        protected ShopifyMappingRepository $shopifyMappingRepository,
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

        $this->initPublications();

        $this->initDefaultLocale();
    }

    /**
     * Initialize credentials data from filters
     */
    protected function initCredential(): void
    {
        $filters = $this->getFilters();

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
     * Initialize publication from credentials data
     */
    protected function initPublications(): void
    {
        if (empty($this->credential->extras['salesChannel'])) {
            return;
        }

        $salesChannel = explode(',', $this->credential->extras['salesChannel']);

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

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('shopify.category.export.before', $batch);

        $this->initialize();

        $this->prepareCategoriesShopify($batch, $filePath);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, ExportHelper::STATE_PROCESSED);

        Event::dispatch('shopify.category.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        return $this->source->with('parent_category')->orderBy('id', 'desc')->all()?->getIterator();
    }

    public function prepareCategoriesShopify(JobTrackBatchContract $batch, mixed $filePath)
    {
        foreach ($batch->data as $rawData) {
            $mapping = $this->checkMappingInDb($rawData) ?? null;
            $localeSpecificFields = $this->getLocaleSpecificFields($rawData, $this->shopifyDefaultLocale);

            $category = [
                'handle' => $rawData['code'] ?? '',
                'title'  => $localeSpecificFields['name'] ?? $rawData['code'],
            ];

            if (empty($mapping)) {
                $responseData = $this->apiRequestShopify($category);
                $resultCollection = $responseData['body']['data']['collectionCreate'] ?? [];
                if (! empty($resultCollection['userErrors'])) {
                    $this->logWarning($resultCollection['userErrors'], $rawData['code']);
                    $this->skippedItemsCount++;

                    continue;
                }

                $this->handleAfterApiRequest($rawData, $responseData, $mapping, $this->export->id, $category);

                $this->createdItemsCount++;
            } else {
                $category['id'] = $mapping[0]['externalId'];
                $responseData = $this->apiRequestShopify($category, $category['id']);
                $resultCollection = $responseData['body']['data']['collectionUpdate'] ?? [];
                $this->logWarning($resultCollection['userErrors'], $rawData['code']);
                if (! empty($resultCollection['userErrors'])) {
                    $resultCollection = $this->handleAfterApiRequest($rawData, $responseData, $mapping, $this->export->id, $category);

                    if (! empty($resultCollection['userErrors']) || empty($resultCollection)) {
                        $this->skippedItemsCount++;
                        $this->logWarning($resultCollection['userErrors'], $rawData['code']);

                        continue;
                    }
                }

                $this->createdItemsCount++;
            }

            if (empty($resultCollection['userErrors']) && ! empty($this->publicationId)) {
                $this->updateSalesChannel($resultCollection, $this->publicationId);
            }

            $this->categoryTranslation($this->shopifyDefaultLocale, $rawData, $this->credential,
                $this->credentialArray, $resultCollection['collection'] ?? []);
        }
    }

    /**
     * Update sales channel of the collection
     */
    public function updateSalesChannel($collectionResult, $publicationIds): void
    {
        $collectionId = $collectionResult['collection']['id'];
        $existingPublications = $collectionResult['collection']['resourcePublications']['edges'] ?? [];

        $existingIds = array_map(fn ($item) => $item['node']['publication']['id'], $existingPublications);
        $newIds = array_column($publicationIds, 'publicationId');
        sort($existingIds);
        sort($newIds);
        if ($existingIds !== $newIds) {
            $this->requestGraphQlApiAction(self::UPDATE_PUBLISH_CHANNEL, $this->credentialArray, [
                'collectionId' => $collectionId,
                'input'        => $publicationIds,
            ]);

            $removePublication = array_values(array_diff($existingIds, $newIds));
            if (! empty($removePublication)) {
                $this->requestGraphQlApiAction(self::UPDATE_UNPUBLISH_CHANNEL, $this->credentialArray, [
                    'collectionId' => $collectionId,
                    'input'        => array_map(fn ($id) => ['publicationId' => $id], $removePublication),
                ]);
            }
        }
    }

    /**
     * log Warning generate
     */
    public function logWarning(array $data, string $code): void
    {
        if (! empty($data) && ! empty($code)) {
            $error = json_encode($data, true);

            $this->jobLogger->warning(
                "Warning for Category with code: {$code}, : {$error}"
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

    /**
     * Make an API request to Shopify to create or update a category.
     */
    public function apiRequestShopify($category, $id = null)
    {
        $mutationType = $id ? 'updateCollection' : 'createCollection';

        $response = $this->requestGraphQlApiAction($mutationType, $this->credentialArray, ['input' => $category]);

        return $response;
    }
}
