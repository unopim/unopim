<?php

declare(strict_types=1);

namespace Webkul\DataTransfer\Jobs\Export\File;

use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

final class SpoutWriterFactory
{
    public const string XLS = 'Xls';

    public const string CSV = 'Csv';

    public const string XLSX = 'Xlsx';

    /**
     * @throws \InvalidArgumentException
     */
    public static function createWriter(string $type, array $normalizedOptions = []): WriterInterface
    {
        $type = ucfirst(strtolower($type));

        switch ($type) {
            case self::CSV:
                $defaults = new CsvOptions;

                return new CsvWriter(new CsvOptions(
                    FIELD_DELIMITER: $normalizedOptions['fieldDelimiter'] ?? $defaults->FIELD_DELIMITER,
                    FIELD_ENCLOSURE: $normalizedOptions['fieldEnclosure'] ?? $defaults->FIELD_ENCLOSURE,
                    SHOULD_ADD_BOM: $normalizedOptions['shouldAddBOM'] ?? $defaults->SHOULD_ADD_BOM,
                ));

            case self::XLSX:
            case self::XLS:
                return new XlsxWriter(new XlsxOptions);

            default:
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid writer type', $type));
        }
    }
}
