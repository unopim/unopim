<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Currency;

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;

class Exporter extends AbstractExporter
{
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

        $currencies = $this->prepareCurrencies($batch);

        $this->exportBuffer->write($currencies);

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        $filters = $this->getFilters();

        $query = $this->source->query();

        if (isset($filters['status']) && $filters['status'] === 'enable') {
            $query->where('status', 1);
        }

        return $query->get()->getIterator();
    }

    /**
     * Prepare currencies from current batch
     *
     * @return array
     */
    public function prepareCurrencies(JobTrackBatchContract $batch)
    {
        $currencies = [];

        foreach ($batch->data as $rowData) {
            $currencies[] = [
                'code'    => $rowData['code'],
                'name'    => $rowData['name'],
                'symbol'  => $rowData['symbol'],
                'decimal' => $rowData['decimal'],
                'status'  => $rowData['status'],
            ];

            $this->createdItemsCount++;
        }

        return $currencies;
    }
}
