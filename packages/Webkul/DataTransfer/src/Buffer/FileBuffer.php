<?php

namespace Webkul\DataTransfer\Buffer;

use Maatwebsite\Excel\Files\TemporaryFile;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\WriterInterface;
use Webkul\DataTransfer\Jobs\Export\File\SpoutWriterFactory;
use Webkul\DataTransfer\Jobs\Export\File\TemporaryFileFactory;

class FileBuffer
{
    const FOLDER_PREFIX = 'uno-pim';

    const PUBLIC_STORAGE_PATH = 'app/public/';

    const PRIVATE_STORAGE_PATH = 'app/private/';

    protected mixed $highestRow;

    /**
     * @var Spreadsheet
     */
    protected mixed $spreadsheet;

    protected array $headers = [];

    protected mixed $filePath;

    protected mixed $writer = null;

    public function make(string $directory, ?string $fileExtension = null, ?string $fileName = null): TemporaryFile
    {
        $temporaryFileFactory = new TemporaryFileFactory($directory);

        return $temporaryFileFactory->make($fileExtension, $fileName);
    }

    protected function getWriter(mixed $filePath, array $options = []): WriterInterface
    {
        if (! isset($options['type'])) {
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
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add the specified keys to the list of headers
     */
    public function addToHeaders(array $keys): void
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
