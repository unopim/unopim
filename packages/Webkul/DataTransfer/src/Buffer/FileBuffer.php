<?php

namespace Webkul\DataTransfer\Buffer;

use Maatwebsite\Excel\Files\TemporaryFile;
use Webkul\DataTransfer\Jobs\Export\File\SpoutWriterFactory;
use Webkul\DataTransfer\Jobs\Export\File\TemporaryFileFactory;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

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

    protected $writer;

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
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }

    protected function getWriter($filePath, array $options = [])
    {
        if (!isset($options['type'])) {
            throw new \InvalidArgumentException('Option "type" have to be defined');
        }

        $writer = SpoutWriterFactory::createWriter($options['type'], $options);
        
        $writer->openToFile($filePath->sync()->getLocalPath());

        return $writer;
    }

    public function writeHeader(array $headers): void
    {
        $this->writer->addRow(Row::fromValues($headers));
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

    public function escapeFormulaCells(Row $row): Row
    {
        $escapedCells = array_map(static function (Cell $cell): Cell {
            if ($cell instanceof Cell\FormulaCell) {
                return new Cell\StringCell($cell->getValue(), null);
            }

            return $cell;
        }, $row->getCells());

        return new Row($escapedCells);
    }
}
