<?php

namespace Webkul\DataTransfer\Helpers\Sources;

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;

abstract class AbstractSource
{
    /**
     * Resolve a source path on the private disk, refusing one that no longer exists.
     *
     * A profile stores a relative path, so the file it points at can disappear
     * between runs. Opening it unchecked lets PHP raise a warning carrying the
     * resolved absolute path, which then reaches whoever ran the job.
     *
     * @throws \LogicException when the file is missing or unreadable
     */
    protected static function resolveReadablePath(string $filePath): string
    {
        $path = Storage::disk('private')->path($filePath);

        if (! is_file($path) || ! is_readable($path)) {
            throw new \LogicException(trans('data_transfer::app.validation.errors.file-missing'));
        }

        return $path;
    }

    /**
     * Column names
     */
    protected array $columnNames = [];

    /**
     * Quantity of columns
     */
    protected int $totalColumns = 0;

    /**
     * Current row
     */
    protected array $currentRowData = [];

    /**
     * Current row number
     */
    protected int $currentRowNumber = -1;

    /**
     * Flag to indicate that wrong quote was found
     */
    protected bool $foundWrongQuoteFlag = false;

    /**
     * Read next line from source
     */
    abstract protected function getNextRow(): array|bool;

    /**
     * Return the key of the current row
     */
    public function getCurrentRowNumber(): int
    {
        return $this->currentRowNumber;
    }

    /**
     * Checks if current position is valid
     */
    public function valid(): bool
    {
        return $this->currentRowNumber !== -1;
    }

    /**
     * Read next line from source
     */
    public function current(): array
    {
        $row = $this->currentRowData;

        if (count($row) !== $this->totalColumns) {
            throw_if($this->foundWrongQuoteFlag, \InvalidArgumentException::class, AbstractImporter::ERROR_CODE_WRONG_QUOTES);
            throw new \InvalidArgumentException(AbstractImporter::ERROR_CODE_COLUMNS_NUMBER);
        }

        return array_combine($this->columnNames, $row);
    }

    /**
     * Read next line from source
     */
    public function next(): void
    {
        $this->currentRowNumber++;

        $row = $this->getNextRow();

        if ($row === false || $row === []) {
            $this->currentRowData = [];

            $this->currentRowNumber = -1;
        } else {
            $this->currentRowData = $row;
        }
    }

    /**
     * Rewind the iterator to the first row
     */
    public function rewind(): void
    {
        $this->currentRowNumber = 0;

        $this->currentRowData = [];

        $this->getNextRow();

        $this->next();
    }

    /**
     * Column names getter.
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * Return total number of columns.
     */
    public function getTotalColumns(): int
    {
        return $this->totalColumns;
    }
}
