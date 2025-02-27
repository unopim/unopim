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
use Webkul\Product\Facades\ProductValueMapper as ProductValueMapperFacade;

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
                    $commonFields = ProductValueMapperFacade::getCommonFields($rowData);
                    unset($commonFields['sku']);
                    $localeSpecificFields = ProductValueMapperFacade::getLocaleSpecificFields($rowData, $locale);
                    $channelSpecificFields = ProductValueMapperFacade::getChannelSpecificFields($rowData, $channel);
                    $channelLocaleSpecificFields = ProductValueMapperFacade::getChannelLocaleSpecificFields($rowData, $channel, $locale);
                    // Merge common and locale-specific fields before array_merge
                    $mergedFields = array_merge($commonFields, $localeSpecificFields, $channelSpecificFields, $channelLocaleSpecificFields);
                    $values = $this->setAttributesValues($mergedFields, $filePath);

                    $data = array_merge([
                        'channel'                 => $channel,
                        'locale'                  => $locale,
                        'sku'                     => $rowData['sku'],
                        'status'                  => $rowData['status'] ? 'true' : 'false',
                        'type'                    => $rowData['type'],
                        'parent'                  => $rowData['parent']['sku'] ?? null,
                        'attribute_family'        => $rowData['attribute_family']['code'] ?? null,
                        'configurable_attributes' => $this->getSuperAttributes($rowData),
                        'categories'              => ProductValueMapperFacade::getCategories($rowData),
                        'up_sells'                => ProductValueMapperFacade::getAssociations($rowData, 'up_sells'),
                        'cross_sells'             => ProductValueMapperFacade::getAssociations($rowData, 'cross_sells'),
                        'related_products'        => ProductValueMapperFacade::getAssociations($rowData, 'related_products'),
                    ], $values);

                    $products[] = $data;
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
     *
     * @return array
     */
    protected function setAttributesValues(array $values, mixed $filePath)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) $filters['with_media'];

        foreach ($this->attributes as $key => $attribute) {
            $attributeCode = $attribute->code;

            if ($attributeCode == 'sku' || $attributeCode === 'status') {
                continue;
            }

            $attributeValues[$attributeCode] = $values[$attributeCode] ?? null;

            if ($attribute->type == AttributeTypes::PRICE_ATTRIBUTE_TYPE) {
                $priceData = ! empty($attributeValues[$attributeCode]) ? $attributeValues[$attributeCode] : [];

                foreach ($this->currencies as $value) {
                    $attributeValues[$attributeCode.' ('.$value.')'] = $priceData[$value] ?? null;
                }

                unset($attributeValues[$attributeCode]);
            }

            if ($withMedia && in_array($attribute->type, [AttributeTypes::FILE_ATTRIBUTE_TYPE, AttributeTypes::IMAGE_ATTRIBUTE_TYPE, AttributeTypes::GALLERY_ATTRIBUTE_TYPE])) {
                $existingFilePath = $values[$attributeCode] ?? null;

                $existingFilePath = is_array($existingFilePath) ? $existingFilePath : [$existingFilePath];

                foreach ($existingFilePath as $path) {
                    if ($path && ! empty($path)) {
                        $newfilePath = $filePath->getTemporaryPath().'/'.$path;
                        $this->copyMedia($path, $newfilePath);
                    }
                }

                if (is_array($existingFilePath)) {
                    $attributeValues[$attributeCode] = implode(', ', $existingFilePath);
                }
            }

            if (is_array($attributeValues[$attributeCode] ?? null)) {
                $attributeValues[$attributeCode] = implode(', ', $attributeValues[$attributeCode]);
            }
        }

        return $attributeValues;
    }
}
