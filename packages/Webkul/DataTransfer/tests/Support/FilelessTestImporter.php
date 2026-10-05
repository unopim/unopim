<?php

namespace Webkul\DataTransfer\Tests\Support;

use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Sources\AbstractSource;

class FilelessTestImporter extends AbstractImporter
{
    public static array $rows = [];

    protected array $validColumnNames = ['code', 'status'];

    protected array $permanentAttributes = ['code'];

    protected string $masterAttributeCode = 'code';

    public function getSource(): AbstractSource
    {
        if (! $this->source) {
            $this->source = new FilelessTestSource(self::$rows);
        }

        return $this->source;
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        $this->validatedRows[$rowNumber] = true;

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => ['created' => 0, 'updated' => count($batch->data), 'deleted' => 0],
        ], $batch->id);

        return true;
    }
}
