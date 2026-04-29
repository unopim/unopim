<?php

namespace Webkul\DataTransfer\Helpers\Sources;

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Rules\SeparatorTypes;

class CSV extends AbstractSource
{
    /**
     * UTF-8 BOM bytes.
     */
    protected const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * CSV reader
     */
    protected mixed $reader;

    /**
     * Maximum line length for fgetcsv (0 = unlimited)
     */
    protected int $maxLineLength = 0;

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        string $filePath,
        protected string $delimiter = ','
    ) {
        $detectedSeparator = self::checkSeparator(Storage::disk('private')->path($filePath));

        if ($detectedSeparator === null) {
            throw new \LogicException(trans('data_transfer::app.validation.errors.file-empty'));
        }

        if ($detectedSeparator != $delimiter) {
            throw new \LogicException("Separator '{$delimiter}' is not supported in the provided file.");
        }

        try {
            $this->reader = fopen(Storage::disk('private')->path($filePath), 'r');

            /**
             * Set a larger read buffer for better I/O performance on large files.
             * This reduces the number of system read calls.
             */
            if (is_resource($this->reader)) {
                stream_set_read_buffer($this->reader, 65536);
            }

            $headerRow = $this->normalizeColumnNames(
                fgetcsv($this->reader, $this->maxLineLength, $delimiter) ?: []
            );

            if (
                $headerRow === false
                || $headerRow === null
                || $headerRow === [null]
                || count(array_filter($headerRow, fn ($v) => $v !== null && $v !== '')) === 0
            ) {
                throw new \LogicException(trans('data_transfer::app.validation.errors.file-empty'));
            }

            $this->columnNames = $headerRow;

            $this->totalColumns = count($this->columnNames);
        } catch (\LogicException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \LogicException("Unable to open file: '{$filePath}'");
        }
    }

    /**
     * Determine the separator used in a CSV file.
     */
    public static function checkSeparator(string $filePath): ?string
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return null;
        }

        $line = fgets($handle);
        fclose($handle);

        if ($line === false) {
            return null;
        }

        $separatorCounts = [];
        foreach (SeparatorTypes::SEPERATOR_TYPES as $separator) {
            $separatorCounts[$separator] = substr_count($line, $separator);
        }

        $detectedSeparator = array_keys($separatorCounts, max($separatorCounts));

        return count($detectedSeparator) === 1 ? $detectedSeparator[0] : null;
    }

    /**
     * Close file handle
     *
     * @return void
     */
    public function __destruct()
    {
        if (! is_object($this->reader)) {
            return;
        }

        $this->reader->close();
    }

    /**
     * Read next line from csv
     */
    protected function getNextRow(): array
    {
        $parsed = fgetcsv($this->reader, $this->maxLineLength, $this->delimiter);

        if (is_array($parsed) && count($parsed) != $this->totalColumns) {
            foreach ($parsed as $element) {
                if ($element && strpos($element, "'") !== false) {
                    $this->foundWrongQuoteFlag = true;

                    break;
                }
            }
        } else {
            $this->foundWrongQuoteFlag = false;
        }

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Rewind the iterator to the first row
     */
    public function rewind(): void
    {
        rewind($this->reader);

        parent::rewind();
    }

    /**
     * Normalize header names for compatibility with external CSV generators.
     */
    protected function normalizeColumnNames(array $columnNames): array
    {
        if (! empty($columnNames[0]) && is_string($columnNames[0])) {
            $columnNames[0] = $this->stripUtf8Bom($columnNames[0]);
        }

        return $columnNames;
    }

    /**
     * Remove a UTF-8 BOM from the beginning of a string.
     */
    protected function stripUtf8Bom(string $value): string
    {
        return str_starts_with($value, self::UTF8_BOM)
            ? substr($value, 3)
            : $value;
    }
}
