<?php

namespace Webkul\DataTransfer\Tests\Support;

use Webkul\DataTransfer\Contracts\JobTrackBatch;

class StopOnErrorsTestImporter extends FilelessTestImporter
{
    public static bool $failValidation = false;

    public static array $importedCodes = [];

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if ($rowData['status'] === 'invalid') {
            $this->errorHelper->addError('invalid_status', $rowNumber, 'status', 'Invalid status.');
        }

        return parent::validateRow($rowData, $rowNumber);
    }

    public function validateData(): void
    {
        parent::validateData();

        if (self::$failValidation) {
            throw new \RuntimeException('Validation failed after preparing batches.');
        }
    }

    public function importBatch(JobTrackBatch $batch): bool
    {
        self::$importedCodes = array_merge(self::$importedCodes, array_column($batch->data, 'code'));

        return parent::importBatch($batch);
    }
}
