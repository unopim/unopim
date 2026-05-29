<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Role;

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;

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
        $roles = $this->prepareRoles($batch);

        $this->exportBuffer->write($roles);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * Prepare roles from current batch
     */
    public function prepareRoles(JobTrackBatchContract $batch)
    {
        $roles = [];
        foreach ($batch->data as $rowData) {
            $roles[] = [
                'id'              => $rowData['id'],
                'name'            => EscapeFormulaOperators::escapeValue($rowData['name']),
                'description'     => EscapeFormulaOperators::escapeValue($rowData['description']),
                'permission_type' => $rowData['permission_type'],
                'permissions'     => ! empty($rowData['permissions']) ? implode(',', $rowData['permissions']) : '',
            ];

            $this->createdItemsCount++;
        }

        return $roles;
    }
}
