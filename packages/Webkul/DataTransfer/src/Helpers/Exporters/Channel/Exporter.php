<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Channel;

use Illuminate\Support\Facades\Event;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;

class Exporter extends AbstractExporter
{
    /**
     * Create a new instance.
     *
     * @param  JobTrackBatchRepository  $exportBatchRepository
     * @param  FileExportFileBuffer  $exportFileBuffer
     * @param  ChannelRepository  $channelRepository
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected ChannelRepository $channelRepository,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the file buffer for the export process.
     *
     * @return void
     */
    public function initilize()
    {
        $this->initializeFileBuffer();
    }

    /**
     * Export a batch of data.
     *
     * @param  JobTrackBatchContract  $batch
     * @param  mixed  $filePath
     * @return bool
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $channels = $this->prepareChannels($batch);

        $this->exportBuffer->write($channels);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * Prepare channels from current batch.
     *
     * @param  JobTrackBatchContract  $batch
     * @return array
     */
    public function prepareChannels(JobTrackBatchContract $batch): array
    {
        $channels = [];

        foreach ($batch->data as $rowData) {
            foreach ($rowData['translations'] as $translation) {
                $channels[] = [
                    'locale'        => $translation['locale'],
                    'code'          => $rowData['code'],
                    'name'          => $translation['name'],
                    'root_category' => $rowData['root_category']['code'] ?? null,
                    'locales'       => implode(',', array_column($rowData['locales'], 'code')),
                    'currencies'    => implode(',', array_column($rowData['currencies'], 'code')),
                ];
            }

            $this->createdItemsCount++;
        }

        return $channels;
    }

    /**
     * Get the results for the export.
     *
     * @return \Iterator
     */
    protected function getResults()
    {
        return $this->channelRepository->queryBuilder()->get()->getIterator();
    }
}
