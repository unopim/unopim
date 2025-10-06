<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Product;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;

class Exporter extends AbstractExporter
{
    /**
     * @var array
     */
    protected $channelsAndLocales = [];

    /**
     * @var array
     */
    protected $currencies = [];

    /**
     * @var array
     */
    protected $attributes = [];

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
        protected ProductSource $productSource,
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
        $channels = $this->channelRepository->all();
        foreach ($channels as $channel) {
            $this->currencies = array_unique(array_merge($this->currencies, $channel->currencies->pluck('code')->toArray()));
            $this->channelsAndLocales[$channel->code] = $channel->locales->pluck('code')->toArray();
        }

        $this->attributes = $this->attributeRepository->all();
    }

    /**
     * Start the import process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $products = $this->prepareProducts($batch, $filePath);

        $this->exportBuffer->write($products);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        $requestParam['filters'] = $this->getFilters();

        return $this->productSource->getResults($requestParam, $this->source, self::BATCH_SIZE);
    }

    protected function getItemsFromIds(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        if (! $this->source) {
            $this->source = app(ProductRepository::class);
        }

        return $this->source->whereIn('id', $ids)->get();
    }

    /**
     * Prepare products from current batch
     */
    public function prepareProducts(JobTrackBatchContract $batch, $filePath)
    {
        $products = [];
        $flatIds = array_column($batch->data, 'id');

        $productsByIds = $this->getItemsFromIds($flatIds);

        foreach ($productsByIds as $product) {
            $rowData = $product->toArray();

            $rowData['super_attributes'] = $rowData['type'] === 'configurable'
                ? $product->super_attributes->toArray()
                : [];

            $family = $rowData['attribute_family']['code'] ?? null;
            $parentSku = $rowData['type'] === 'simple'
                ? optional($product->parent)->sku
                : null;

            $sku = $rowData['sku'];
            $type = $rowData['type'];
            $status = $rowData['status'] ? 'true' : 'false';
            $configurableAttributes = $this->getSuperAttributes($rowData);
            $categories = $this->getCategories($rowData);
            $upSells = $this->getAssociations($rowData, 'up_sells');
            $crossSells = $this->getAssociations($rowData, 'cross_sells');
            $relatedProducts = $this->getAssociations($rowData, 'related_products');

            unset($rowData['attribute_family'], $rowData['parent']);

            $commonFields = $this->getCommonFields($rowData);
            unset($commonFields['sku']);

            foreach ($this->channelsAndLocales as $channel => $locales) {
                foreach ($locales as $locale) {
                    $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $locale);
                    $channelSpecificFields = $this->getChannelSpecificFields($rowData, $channel);
                    $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($rowData, $channel, $locale);

                    $mergedFields = array_merge(
                        $commonFields,
                        $localeSpecificFields,
                        $channelSpecificFields,
                        $channelLocaleSpecificFields
                    );

                    $values = $this->setAttributesValues($mergedFields, $filePath);

                    $products[] = array_merge([
                        'channel'                 => $channel,
                        'locale'                  => $locale,
                        'sku'                     => $sku,
                        'status'                  => $status,
                        'type'                    => $type,
                        'parent'                  => $parentSku,
                        'attribute_family'        => $family,
                        'configurable_attributes' => $configurableAttributes,
                        'categories'              => $categories,
                        'up_sells'                => $upSells,
                        'cross_sells'             => $crossSells,
                        'related_products'        => $relatedProducts,
                    ], $values);
                }
            }

            $this->createdItemsCount++;
        }

        return $products;
    }

    public function getSuperAttributes($data)
    {
        if (! isset($data['super_attributes'])) {
            return null;
        }

        $configurable_attributes = array_map(function ($data) {
            return $data['code'];
        }, $data['super_attributes'] ?? []);

        return implode(',', $configurable_attributes);
    }

    /**
     * Sets attribute values for a product. If an attribute is not present in the given values array,
     *
     * @return array
     */
    protected function setAttributesValues(array $values, mixed $filePath)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) ($filters['with_media'] ?? false);

        foreach ($this->attributes as $attribute) {
            $code = $attribute->code;

            if (in_array($code, ['sku', 'status'])) {
                continue;
            }

            $rawValue = $values[$code] ?? null;

            if (
                $withMedia &&
                in_array($attribute->type, [
                    AttributeTypes::FILE_ATTRIBUTE_TYPE,
                    AttributeTypes::IMAGE_ATTRIBUTE_TYPE,
                    AttributeTypes::GALLERY_ATTRIBUTE_TYPE,
                ])
            ) {
                $mediaPaths = (array) $rawValue;
                foreach ($mediaPaths as $path) {
                    if (! empty($path)) {
                        $this->copyMedia($path, $filePath->getTemporaryPath().'/'.$path);
                    }
                }

                $attributeValues[$code] = implode(', ', array_filter($mediaPaths));

                continue;
            }

            if ($attribute->type === AttributeTypes::PRICE_ATTRIBUTE_TYPE) {
                $priceData = is_array($rawValue) ? $rawValue : [];

                foreach ($this->currencies as $currency) {
                    $attributeValues["{$code} ({$currency})"] = $priceData[$currency] ?? null;
                }

                continue;
            }

            if (is_array($rawValue)) {
                $rawValue = implode(', ', $rawValue);
            }

            $attributeValues[$code] = EscapeFormulaOperators::escapeValue($rawValue);
        }

        return $attributeValues;
    }

    /**
     * Retrieves and formats the common fields for a product.
     *
     * @return array
     */
    protected function getCommonFields(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('common', $data['values'])
        ) {
            return [];
        }

        return $data['values']['common'];
    }

    /**
     * Retrieves and formats the locale-specific fields for a product.
     *
     * @return array
     */
    protected function getLocaleSpecificFields(array $data, string $locale)
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
     * Retrieves and formats the channel-specific fields for a product.
     *
     * @return array
     */
    protected function getChannelSpecificFields(array $data, string $channel)
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
     * Retrieves and formats the channel-locale-specific fields for a product.
     *
     * @return array
     */
    protected function getChannelLocaleSpecificFields(array $data, string $channel, string $locale)
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
     * Retrieves and formats the categories associated with a product.
     *
     * @return string|null
     */
    protected function getCategories(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('categories', $data['values'])
            || ! is_array($data['values']['categories'])
        ) {
            return;
        }

        return implode(',', $data['values']['categories']);
    }

    /**
     * Retrieves and formats the associated products for a given data row and type.
     *
     * @return string|null
     */
    protected function getAssociations(array $data, string $type)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('associations', $data['values'])
            || ! is_array($data['values']['associations'])
            || ! array_key_exists($type, $data['values']['associations'])
        ) {
            return;
        }

        return implode(',', $data['values']['associations'][$type]) ?? null;
    }
}
