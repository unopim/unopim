<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Product;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

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

        $this->exportFileBuffer->addData($products, $filePath, $this->getExportParameter());

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
        return $this->source->with([
            'attribute_family',
            'parent',
            'super_attributes',
        ])->orderBy('id', 'desc')->all()?->getIterator();
    }

    /**
     * Prepare products from current batch
     */
    public function prepareProducts(JobTrackBatchContract $batch, $filePath)
    {
        $products = [];
        foreach ($batch->data as $rowData) {
            foreach ($this->channelsAndLocales as $channel => $locales) {
                foreach ($locales as $locale) {
                    $commonFields = $this->getCommonFields($rowData);
                    unset($commonFields['sku']);
                    $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $locale);
                    $channelSpecificFields = $this->getChannelSpecificFields($rowData, $channel);
                    $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($rowData, $channel, $locale);
                    // Merge common and locale-specific fields before array_merge
                    $mergedFields = array_merge($commonFields, $localeSpecificFields, $channelSpecificFields, $channelLocaleSpecificFields);
                    $values = $this->setAttributesValues($mergedFields, $filePath);

                    $data = array_merge([
                        'channel'                 => $channel,
                        'locale'                  => $locale,
                        'sku'                     => $rowData['sku'],
                        'type'                    => $rowData['type'],
                        'parent'                  => $rowData['parent']['sku'] ?? null,
                        'attribute_family'        => $rowData['attribute_family']['code'] ?? null,
                        'configurable_attributes' => $this->getSupperAttributes($rowData),
                        'categories'              => $this->getCategories($rowData),
                        'up_sells'                => $this->getAssociations($rowData, 'up_sells'),
                        'cross_sells'             => $this->getAssociations($rowData, 'cross_sells'),
                        'related_products'        => $this->getAssociations($rowData, 'related_products'),
                    ], $values);

                    $products[] = $data;
                }
            }

            $this->createdItemsCount++;
        }

        return $products;
    }

    public function getSupperAttributes($data)
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
     *
     * @return array
     */
    protected function setAttributesValues(array $values, mixed $filePath)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) $filters['with_media'];

        foreach ($this->attributes as $key => $attribute) {
            if ($attribute->code == 'sku') {
                continue;
            }

            $attributeValues[$attribute->code] = $values[$attribute->code] ?? null;

            if ($attribute->type == AttributeTypes::PRICE_ATTRIBUTE_TYPE) {
                $priceData = ! empty($attributeValues[$attribute->code]) ? $attributeValues[$attribute->code] : [];

                foreach ($this->currencies as $value) {
                    $attributeValues[$attribute->code.' ('.$value.')'] = $priceData[$value] ?? null;
                }

                unset($attributeValues[$attribute->code]);
            }

            if ($withMedia && in_array($attribute->type, [AttributeTypes::FILE_ATTRIBUTE_TYPE, AttributeTypes::IMAGE_ATTRIBUTE_TYPE])) {
                $exitingFilePath = $values[$attribute->code] ?? null;
                if ($exitingFilePath && ! empty($exitingFilePath)) {
                    $newfilePath = $filePath->getTemporaryPath().'/'.$exitingFilePath;
                    $this->copyMedia($exitingFilePath, $newfilePath);
                }
            }
        }

        return $attributeValues;
    }

    /**
     * Retrieves and formats the common fields for a product.
     *
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
     * @param  string  $channel
     * @return array
     */
    protected function getLocaleSpecificFields(array $data, $locale)
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
     * @param  string  $channel
     * @return array
     */
    protected function getChannelSpecificFields(array $data, $channel)
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
