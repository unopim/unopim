<?php

declare(strict_types=1);

namespace Webkul\DataTransfer\Jobs\Export\File;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

final class SpoutWriterFactory
{
    public const CSV = 'Csv';

    public const XLS = 'Xls';

    public const XLSX = 'Xlsx';

    public static function createSpreadSheet()
    {
        return new Spreadsheet();
    }

    public static function createWriter(string $type, $spreadsheet, array $normalizedOptions = [])
    {
        switch ($type) {
            case self::CSV:
                $writer = new CsvWriter($spreadsheet);
                $writer->setDelimiter($normalizedOptions['fieldDelimiter']);
                $writer->setEnclosure($normalizedOptions['filedEnclosure']);
                // $writer->setSheetIndex(0);
                // $options->SHOULD_ADD_BOM = $normalizedOptions['shouldAddBOM'] ?? $options->SHOULD_ADD_BOM;
                break;
            case self::XLS:
                $writer = new XlsWriter($spreadsheet);
                break;
            case self::XLSX:
                $writer = new XlsxWriter($spreadsheet);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid writer type', $type));
        }

        return $writer;
    }
}
