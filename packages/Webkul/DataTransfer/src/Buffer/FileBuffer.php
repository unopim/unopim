<?php

namespace Webkul\DataTransfer\Buffer;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Webkul\DataTransfer\Jobs\Export\File\LocalTemporaryFile;
use Webkul\DataTransfer\Jobs\Export\File\RemoteTemporaryFile;
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

    protected $writer;

    public function make($directory, ?string $fileExtension = null, ?string $fileName = null): RemoteTemporaryFile|LocalTemporaryFile
    {
        $temporaryFileFactory = new TemporaryFileFactory($directory);

        return $temporaryFileFactory->make($fileExtension, $fileName);
    }

    protected function getWriter($filePath, array $options = [])
    {
        throw_unless(isset($options['type']), \InvalidArgumentException::class, 'Option "type" have to be defined');

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
    public function addToHeaders(array $keys): void
    {
        $headers = array_merge($this->headers, $keys);
        $headers = array_unique($headers);

        $this->headers = $headers;
    }

    public function escapeFormulaCells(Row $row): Row
    {
        $escapedCells = array_map(function (Cell $cell): Cell {
            $value = $cell->getValue();

            if (is_string($value) && $this->isFormulaValue($value)) {
                return new Cell\StringCell("'".$value, null);
            }

            if ($cell instanceof Cell\FormulaCell) {
                return new Cell\StringCell($value, null);
            }

            return $cell;
        }, $row->getCells());

        return new Row($escapedCells);
    }

    /**
     * Determine whether a cell value would be interpreted as a formula by a
     * spreadsheet application (CSV/XLSX formula-injection guard).
     */
    protected function isFormulaValue(string $value): bool
    {
        if ($value === '' || is_numeric($value)) {
            return false;
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return true;
        }

        $trimmed = ltrim($value, " \t\r\n");

        return $trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true);
    }
}
