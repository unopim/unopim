<?php

namespace Webkul\DataTransfer\Helpers\Exporters\AttributeOption;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected AttributeOptionRepository $attributeOptionRepository,
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
        $this->initializeFileBuffer();
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();
        $attributeOptions = $this->prepareAttributeOptions($batch, $filePath);

        $this->exportBuffer->write($attributeOptions);

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
        return $this->source->with('attribute')->get()?->getIterator();
    }

    /**
     * Prepare attribute options from current batch
     */
    public function prepareAttributeOptions(JobTrackBatchContract $batch, mixed $filePath)
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $attributeOptions = [];

        foreach ($batch->data as $rowData) {
            $translations = collect($rowData['translations'] ?? [])->keyBy('locale')->toArray();

            foreach ($locales as $locale) {
                $data = [
                    'attribute_code'   => $rowData['attribute']['code'] ?? null,
                    'code'             => $rowData['code'] ?? null,
                    'locale'           => $locale,
                    'label'            => $translations[$locale]['label'] ?? null,
                    'sort_order'       => $rowData['sort_order'] ?? null,
                    'swatch_value'     => $rowData['swatch_value'] ?? null,
                ];

                $attributeOptions[] = $data;
            }

            $this->createdItemsCount++;
        }

        return $attributeOptions;
    }
}
