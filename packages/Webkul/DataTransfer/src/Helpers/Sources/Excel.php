<?php

namespace Webkul\DataTransfer\Helpers\Sources;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class Excel extends AbstractSource
{
    /**
     * CSV reader
     */
    protected mixed $reader;

    /**
     * Current row number
     */
    protected int $currentRowNumber = 1;

    /**
     * Create a new helper instance.
     */
    public function __construct(string $filePath)
    {
        try {
            $factory = IOFactory::load(Storage::disk('private')->path($filePath));

            $this->reader = $factory->getActiveSheet();

            $highestColumn = $this->reader->getHighestColumn();

            $this->totalColumns = Coordinate::columnIndexFromString($highestColumn);

            $headerRow = $this->getNextRow();

            if ($headerRow === false || $headerRow === []) {
                throw new \LogicException(trans('data_transfer::app.validation.errors.file-empty'));
            }

            $this->columnNames = $headerRow;
        } catch (\LogicException $e) {
            throw $e;
        } catch (\Exception) {
            throw new \LogicException("Unable to open file: '{$filePath}'");
        }
    }

    /**
     * Read next line from csv
     */
    protected function getNextRow(): array|bool
    {
        for ($column = 1; $column <= $this->totalColumns; $column++) {
            $value = $this->reader->getCellByColumnAndRow($column, $this->currentRowNumber)->getValue();

            $rowData[] = $value instanceof RichText ? $value->getPlainText() : $value;
        }

        $filteredRowData = array_filter($rowData);

        if ($filteredRowData === []) {
            return false;
        }

        return $rowData;
    }

    /**
     * Rewind the iterator to the first row
     */
    #[\Override]
    public function rewind(): void
    {
        $this->currentRowNumber = 1;

        $this->next();
    }
}
