<?php

namespace Webkul\Shopify\Helpers\Importers\Category;

use Illuminate\Support\Arr;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyMappingRepository;
use Webkul\Shopify\Traits\DataMappingTrait;
use Webkul\Shopify\Traits\ShopifyGraphqlRequest;
use Webkul\Shopify\Traits\ValidatedBatched;

class Importer extends AbstractImporter
{
    use DataMappingTrait;
    use ShopifyGraphqlRequest;
    use ValidatedBatched;

    public const BATCH_SIZE = 10;

    public const UNOPIM_ENTITY_NAME = 'category';

    /**
     * cursor position
     */
    public $cursor = null;

    protected array $categoryFields;

    /**
     * locales storage
     */
    protected array $locales = [];

    /**
     * Shopify credential.
     *
     * @var mixed
     */
    protected $credential;

    /**
     * Shopify job Locale.
     *
     * @var mixed
     */
    protected $locale;

    /**
     * Shopify credential as array for api request.
     *
     * @var mixed
     */
    protected $credentialArray;

    protected $cachedCategoryFields = [];

    protected ?array $nonDeletableCategories = null;

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected Storage $categoryStorage,
        protected LocaleRepository $localeRepository,
        protected ChannelRepository $channelRepository,
        protected ShopifyCredentialRepository $shopifyRepository,
        protected ShopifyMappingRepository $shopifyMappingRepository,
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
        $this->categoryStorage->init();
        $this->initFilters();
        if (! $this->credential?->active) {
            throw new \InvalidArgumentException('Invalid Credential: The credential is either disabled, incorrect, or does not exist');
        }
        $this->credentialArray = [
            'shopUrl'     => $this->credential?->shopUrl,
            'accessToken' => $this->credential?->accessToken,
            'apiVersion'  => $this->credential?->apiVersion,
        ];

        $collections = new \Webkul\Shopify\Helpers\Iterator\CategoryIterator($this->credentialArray);

        return $collections;
    }

    /**
     * Save categories from current batch
     */
    public function saveCategories(array $categories): void
    {
        /** single insert/update in the db because of parent  */
        if (! empty($categories['update'])) {
            $this->updatedItemsCount += count($categories['update']);
            foreach ($categories['update'] as $code => $category) {
                $this->categoryRepository->update($category, $this->categoryStorage->get($code), withoutFormattingValues: true);
            }
        }

        if (! empty($categories['insert'])) {
            $this->createdItemsCount += count($categories['insert']);
            foreach ($categories['insert'] as $code => $category) {
                $newCategory = $this->categoryRepository->create($category, withoutFormattingValues: true);
                if ($newCategory) {
                    $this->categoryStorage->set($code, $newCategory?->id);
                }
            }
        }
    }

    public function validateData(): void
    {
        $this->saveValidatedBatches();
    }

    /**
     * Start the import process for Category Import
     */
    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->saveCategoryData($batch);

        return true;
    }

    /**
     * save the category data
     */
    public function saveCategoryData(JobTrackBatchContract $batch): bool
    {
        $this->initFilters();
        $collectionData = array_column($batch->data, 'node');
        $this->categoryStorage->load(Arr::pluck($collectionData, 'handle'));
        $categories = [];
        foreach ($batch->data as $rowData) {
            /**
             * Prepare categories for import
             */
            $this->prepareCategories($rowData, $categories);
        }
        $this->saveCategories($categories);
        /**
         * Update import batch summary
         */
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
     * Prepare categories for import
     */
    public function prepareCategories(array $collection, &$category)
    {
        $categ = $this->categoryRepository->where('code', $collection['node']['handle'])->first();

        $data = [
            'code'               => $collection['node']['handle'],
            'parent_id'          => $categ?->parent_id,
            'additional_data'    => $categ ? $categ->toArray()['additional_data'] : [],
        ];

        $categoryMapping = $this->checkMappingInDb(['code' => $collection['node']['handle']]);
        if (! $categoryMapping) {
            $this->parentMapping($collection['node']['handle'], $collection['node']['id'], $this->import->id);
        }

        if ($categ) {
            $data['additional_data'] = $this->mergeCategoryFieldValues($data['additional_data'] ?? [], $category['update'][$collection['node']['handle']]['additional_data'] ?? []);
            $data['additional_data']['locale_specific'][$this->locale]['name'] = $collection['node']['title'] ?? $data['additional_data']['locale_specific'][$this->locale]['name'];
            $category['update'][$collection['node']['handle']] = array_merge($category['update'][$collection['node']['handle']] ?? [], $data);
        } else {
            $data['additional_data']['locale_specific'][$this->locale]['name'] = $collection['node']['title'];
            $data['additional_data'] = $this->mergeCategoryFieldValues($data['additional_data'], $category['insert'][$collection['node']['handle']]['additional_data'] ?? []);

            $category['insert'][$collection['node']['handle']] = array_merge($category['insert'][$collection['node']['handle']] ?? [], $data);
        }
    }

    /**
     * Merge Attribute values for each section with previous section
     */
    protected function mergeCategoryFieldValues(array $newValues, array $oldValues): array
    {
        if (! empty($oldValues[CategoryRepository::COMMON_VALUES_KEY])) {
            $newValues[CategoryRepository::COMMON_VALUES_KEY] = array_filter(
                array_merge($newValues[CategoryRepository::COMMON_VALUES_KEY] ?? [], $oldValues[CategoryRepository::COMMON_VALUES_KEY])
            );
        }

        foreach ($this->locales as $localeCode) {
            $newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] = array_filter(
                array_merge($newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] ?? [], $oldValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode] ?? [])
            );

            if (empty($newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode])) {
                unset($newValues[CategoryRepository::LOCALE_VALUES_KEY][$localeCode]);
            }
        }

        return array_filter($newValues);
    }

    public function getCategoryFields()
    {
        if (! isset($this->categoryFields)) {
            $this->cachedCategoryFields = $this->categoryFieldRepository->where('status', 1)->get();

            $this->categoryFields = $this->cachedCategoryFields->pluck('code')->toArray();
        }

        return $this->categoryFields;
    }

    /**
     * Check if category code exists
     */
    public function isCategoryExist(string $code): bool
    {
        return $this->categoryStorage->has($code);
    }

    /**
     * Categories Getting by cursor
     */
    public function getCategoriesByCursor(): array
    {
        $cursor = null;
        $allCollections = [];

        do {
            $variables = [
                'first' => 5,
            ];
            $collectionGettingType = 'manualCollectionGetting';
            if ($cursor) {
                $variables['afterCursor'] = $cursor;
                $collectionGettingType = 'GetCollectionsByCursor';
            }
            $graphResponse = $this->requestGraphQlApiAction($collectionGettingType, $this->credentialArray, $variables);

            $graphqlCollection = ! empty($graphResponse['body']['data']['collections']['edges'])
                ? $graphResponse['body']['data']['collections']['edges']
                : [];
            $allCollections = array_merge($allCollections, $graphqlCollection);

            $lastCursor = ! empty($graphqlCollection) ? end($graphqlCollection)['cursor'] : null;

            if ($cursor === $lastCursor || empty($lastCursor)) {
                break;
            }
            $cursor = $lastCursor;

        } while (! empty($graphqlCollection));

        return $allCollections;
    }

    /**
     * Validates row
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        return true;
    }
}
