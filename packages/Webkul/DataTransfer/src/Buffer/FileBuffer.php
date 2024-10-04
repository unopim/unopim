<?php

namespace Webkul\DataTransfer\Buffer;

use Maatwebsite\Excel\Files\TemporaryFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\DataTransfer\Jobs\Export\File\SpoutWriterFactory;
use Webkul\DataTransfer\Jobs\Export\File\TemporaryFileFactory;

class FileBuffer
{
    const FOLDER_PREFIX = 'uno-pim';

    const PUBLIC_STORAGE_PATH = 'app/public/';

    const PRIVATE_STORAGE_PATH = 'app/private/';

    protected $highestRow;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;

    /** @var array */
    protected $headers = [];

    protected $filePath;

    protected $fileHandle;

    /**
     * @return TemporaryFile
     */
    public function make($directory, ?string $fileExtension = null, ?string $fileName = null)
    {
        $temporaryFileFactory = new TemporaryFileFactory($directory);

        return $temporaryFileFactory->make($fileExtension, $fileName);
    }

    /**
     * Close and delete file at buffer destruction
     */
    public function __destruct()
    {
        unset($this->fileHandle);
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }

    public function appendRows(array $item, $sheet)
    {
        $column = 'A';
        foreach ($item as $cellValue) {
            $sheet->setCellValue($column.$this->highestRow, $cellValue);
            $column++;
        }
    }

    /**
     * @return Writer
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function reopen(TemporaryFile $tempFile, string $writerType, $options)
    {
        $reader = IOFactory::createReader($writerType);
        try {
            $this->spreadsheet = $reader->load($tempFile->sync()->getLocalPath());
        } catch (\Exception $e) {
            $this->spreadsheet = SpoutWriterFactory::createSpreadSheet();
            $writer = SpoutWriterFactory::createWriter($writerType, $this->spreadsheet, $options);
            $writer->save($tempFile->sync()->getLocalPath());
        }

        return $this;
    }

    public function setHeaders()
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        $headers = $this->getHeaders();
        $column = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue($column.'1', $header);
            $column++;
        }
    }

    /**
     * Return the headers of every columns
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Add the specified keys to the list of headers
     */
    public function addToHeaders(array $keys)
    {
        $headers = array_merge($this->headers, $keys);
        $headers = array_unique($headers);

        $this->headers = $headers;
    }

    public function current(): mixed
    {
        $rawLine = $this->fileHandle->current();

        return json_decode($rawLine, true);
    }

    public function next(): void
    {
        $this->fileHandle->next();
    }

    public function key(): int
    {
        return $this->fileHandle->key();
    }

    public function valid(): bool
    {
        return $this->fileHandle->valid();
    }

    public function rewind(): void
    {
        $this->fileHandle->rewind();
    }
}
