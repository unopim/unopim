<?php

declare(strict_types=1);

namespace Webkul\DataTransfer\Jobs\Export\File;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;

final class XlsFileConverter
{
    public const int MAX_ROWS = 65536;

    public const int MAX_COLUMNS = 256;

    /**
     * Most cells converted in one export, so the conversion finishes well inside a queue worker timeout.
     */
    public const int MAX_CELLS = 500_000;

    /**
     * Peak memory one cell takes while PhpSpreadsheet holds the workbook for conversion.
     */
    public const int BYTES_PER_CELL = 1024;

    /**
     * Fail when a sheet of the given size cannot be written as XLS or converted in the available memory.
     *
     * @throws \RuntimeException
     */
    public function assertFits(int $rows, int $columns): void
    {
        if ($columns > self::MAX_COLUMNS) {
            throw new \RuntimeException(trans('data_transfer::app.exporters.xls-too-many-columns', [
                'limit' => self::MAX_COLUMNS,
            ]));
        }

        if ($rows > self::MAX_ROWS) {
            throw new \RuntimeException(trans('data_transfer::app.exporters.xls-too-many-rows', [
                'limit' => number_format(self::MAX_ROWS),
            ]));
        }

        $cellLimit = $this->cellLimit();

        if ($rows * $columns > $cellLimit) {
            throw new \RuntimeException(trans('data_transfer::app.exporters.xls-too-large', [
                'rows'    => number_format($rows),
                'columns' => number_format($columns),
                'limit'   => number_format($cellLimit),
            ]));
        }
    }

    /**
     * Most cells this process can convert, bounded by MAX_CELLS and by the free memory under memory_limit.
     */
    public function cellLimit(): int
    {
        $memoryLimit = ini_parse_quantity(ini_get('memory_limit'));

        if ($memoryLimit <= 0) {
            return self::MAX_CELLS;
        }

        $freeBytes = max(0, $memoryLimit - memory_get_usage(true));

        return min(self::MAX_CELLS, intdiv($freeBytes, self::BYTES_PER_CELL));
    }

    /**
     * Rewrite the XLSX workbook at the given path, in place, as an Excel 97-2003 workbook.
     *
     * @throws \RuntimeException
     */
    public function convert(string $path): void
    {
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        $convertedPath = $path.'.converting';

        try {
            new XlsWriter($spreadsheet)->save($convertedPath);
        } catch (\Throwable $throwable) {
            File::delete($convertedPath);

            throw $throwable;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        if (! File::move($convertedPath, $path)) {
            File::delete($convertedPath);

            throw new \RuntimeException(trans('data_transfer::app.exporters.xls-conversion-failed'));
        }
    }
}
