<?php

namespace Webkul\DataTransfer\Helpers\Exporters\AttributeGroup;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
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
        protected AttributeGroupRepository $attributeGroupRepository,
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
        $attributeGroups = $this->prepareAttributeGroups($batch, $filePath);

        $this->exportBuffer->write($attributeGroups);

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
        return $this->source->all()?->getIterator();
    }

    /**
     * Prepare attribute groups from current batch
     */
    public function prepareAttributeGroups(JobTrackBatchContract $batch, mixed $filePath)
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $attributeGroups = [];

        foreach ($batch->data as $rowData) {
            $translations = collect($rowData['translations'] ?? [])->keyBy('locale')->toArray();

            foreach ($locales as $locale) {
                $data = [
                    'code'     => $rowData['code'] ?? null,
                    'locale'   => $locale,
                    'name'     => $translations[$locale]['name'] ?? null,
                    'column'   => $rowData['column'] ?? null,
                    'position' => $rowData['position'] ?? null,
                ];

                $attributeGroups[] = $data;
            }

            $this->createdItemsCount++;
        }

        return $attributeGroups;
    }
}
