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
    public const XLS = 'Xls';
    public const CSV = 'Csv';
    public const XLSX = 'Xlsx';

    /**
     * @throws \InvalidArgumentException
     */
    public static function createWriter(string $type, array $normalizedOptions = []): WriterInterface
    {
        $type = ucfirst(strtolower($type));

        switch ($type) {
            case self::CSV:
                $options = new CsvOptions();
                $options->FIELD_DELIMITER = $normalizedOptions['fieldDelimiter'] ?? $options->FIELD_DELIMITER;
                $options->FIELD_ENCLOSURE = $normalizedOptions['fieldEnclosure'] ?? $options->FIELD_ENCLOSURE;
                $options->SHOULD_ADD_BOM = $normalizedOptions['shouldAddBOM'] ?? $options->SHOULD_ADD_BOM;
                return new CsvWriter($options);

            case self::XLSX:
            case self::XLS:
                $options = new XlsxOptions();
                return new XlsxWriter($options);

            default:
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid writer type', $type));
        }
    }
}
