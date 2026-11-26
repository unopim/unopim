<?php

namespace Webkul\DataTransfer\Helpers\Sources;

use Webkul\DataTransfer\Rules\SeparatorTypes;

class CSV extends AbstractSource
{
    /**
     * CSV reader
     */
    protected mixed $reader;

    /**
     * Maximum line length for fgetcsv (0 = unlimited)
     */
    protected int $maxLineLength = 0;

    /**
     * Resolved path of the source file.
     */
    protected string $path;

    /**
     * Create a new helper instance.
     */
    public function __construct(
        string $filePath,
        protected string $delimiter = ','
    ) {
        $path = $this->path = self::resolveReadablePath($filePath);

        $detectedSeparator = self::checkSeparator($path);

        if ($detectedSeparator === null) {
            throw new \LogicException(trans('data_transfer::app.validation.errors.file-empty'));
        }

        throw_if($detectedSeparator !== $delimiter, \LogicException::class, "Separator '{$delimiter}' is not supported in the provided file.");

        try {
            $this->reader = fopen($path, 'r');

            /**
             * Set a larger read buffer for better I/O performance on large files.
             * This reduces the number of system read calls.
             */
            if (is_resource($this->reader)) {
                stream_set_read_buffer($this->reader, 65536);
            }

            $headerRow = fgetcsv($this->reader, $this->maxLineLength, $delimiter, escape: '\\');

            if (
                in_array($headerRow, [false, null, [null]], true)
                || count(array_filter($headerRow, fn ($v): bool => $v !== null && $v !== '')) === 0
            ) {
                throw new \LogicException(trans('data_transfer::app.validation.errors.file-empty'));
            }

            $this->columnNames = $headerRow;

            $this->totalColumns = count($this->columnNames);
        } catch (\LogicException $e) {
            throw $e;
        } catch (\Exception) {
            throw new \LogicException("Unable to open file: '{$filePath}'");
        }
    }

    /**
     * Determine the separator used in a CSV file.
     */
    public static function checkSeparator(string $filePath): ?string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return null;
        }

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
        $parsed = fgetcsv($this->reader, $this->maxLineLength, $this->delimiter, escape: '\\');

        if (is_array($parsed) && count($parsed) !== $this->totalColumns) {
            foreach ($parsed as $element) {
                if ($element && str_contains($element, "'")) {
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
     * Open a fresh handle on the source file, replacing the current one.
     */
    public function reopen(): void
    {
        $reader = @fopen($this->path, 'r');

        throw_unless(is_resource($reader), \LogicException::class, trans('data_transfer::app.validation.errors.file-missing'));

        stream_set_read_buffer($reader, 65536);

        if (is_resource($this->reader)) {
            fclose($this->reader);
        }

        $this->reader = $reader;
    }

    /**
     * Rewind the iterator to the first row
     */
    public function rewind(): void
    {
        rewind($this->reader);

        parent::rewind();
    }
}
