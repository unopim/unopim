<?php

namespace Webkul\DataTransfer\Helpers\Exporters\AttributeFamily;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
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
        protected AttributeFamilyRepository $attributeFamilyRepository,
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
        $attributeFamilies = $this->prepareAttributeFamilies($batch, $filePath);

        $this->exportBuffer->write($attributeFamilies);

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
     * Prepare attribute families from current batch
     */
    public function prepareAttributeFamilies(JobTrackBatchContract $batch, mixed $filePath)
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $attributeFamilies = [];

        foreach ($batch->data as $rowData) {
            $translations = collect($rowData['translations'] ?? [])->keyBy('locale')->toArray();

            foreach ($locales as $locale) {
                $data = [
                    'code'   => $rowData['code'] ?? null,
                    'locale' => $locale,
                    'name'   => $translations[$locale]['name'] ?? null,
                ];

                $attributeFamilies[] = $data;
            }

            $this->createdItemsCount++;
        }

        return $attributeFamilies;
    }
}
