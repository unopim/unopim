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
     * Rewrite the XLSX workbook at the given path, in place, as an Excel 97-2003 workbook.
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

        rename($convertedPath, $path);
    }
}
