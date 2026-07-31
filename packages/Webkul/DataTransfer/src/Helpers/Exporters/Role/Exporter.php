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
     */
    public function initilize(): void
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

        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * Prepare roles from current batch
     *
     * @return array{name: mixed, description: mixed, permission_type: mixed, permissions: string}[]
     */
    public function prepareRoles(JobTrackBatchContract $batch): array
    {
        $roles = [];
        foreach ($batch->data as $rowData) {
            $roles[] = [
                'name'            => EscapeFormulaOperators::escapeValue($rowData['name']),
                'description'     => EscapeFormulaOperators::escapeValue($rowData['description']),
                'permission_type' => $rowData['permission_type'],
                'permissions'     => empty($rowData['permissions']) ? '' : implode(',', $rowData['permissions']),
            ];

            $this->createdItemsCount++;
        }

        return $roles;
    }
}
