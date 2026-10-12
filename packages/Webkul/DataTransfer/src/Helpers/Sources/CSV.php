<?php

namespace Webkul\DataTransfer\Helpers\Sources;

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
     */
    public function __construct(
        string $filePath,
        protected string $delimiter = ','
    ) {
        $path = self::resolveReadablePath($filePath);

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

                $this->skipUtf8Bom();
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
     * Rewind the iterator to the first row
     */
    public function rewind(): void
    {
        rewind($this->reader);

        $this->skipUtf8Bom();

        parent::rewind();
    }

    /**
     * Move the reader past a leading UTF-8 BOM so it never reaches the first column name.
     */
    protected function skipUtf8Bom(): void
    {
        if (fread($this->reader, strlen(self::UTF8_BOM)) !== self::UTF8_BOM) {
            rewind($this->reader);
        }
    }
}
