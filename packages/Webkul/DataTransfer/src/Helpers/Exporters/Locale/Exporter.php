<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Locale;

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;

class Exporter extends AbstractExporter
{
    /**
     * Initializes the export process.
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

        $locales = $this->prepareLocales($batch);

        $this->exportBuffer->write($locales);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * Prepare locales from current batch.
     *
     * Applies the optional `status` filter:
     *  - 'enable' → export only active locales (status == 1)
     *  - 'All' or absent → export all locales
     */
    public function prepareLocales(JobTrackBatchContract $batch): array
    {
        $locales = [];

        $statusFilter = $this->getFilters()['status'] ?? null;

        foreach ($batch->data as $rowData) {
            if ($statusFilter === 'enable' && ! $rowData['status']) {
                $this->skippedItemsCount++;

                continue;
            }

            $locales[] = [
                'id'     => $rowData['id'],
                'code'   => $rowData['code'],
                'name'   => $rowData['name'],
                'status' => $rowData['status'],
            ];

            $this->createdItemsCount++;
        }

        return $locales;
    }
}
