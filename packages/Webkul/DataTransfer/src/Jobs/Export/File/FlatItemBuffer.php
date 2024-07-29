<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use Webkul\DataTransfer\Buffer\BufferInterface;
use Webkul\DataTransfer\Buffer\FileBuffer;

/**
 * Puts items into a buffer and calculate headers during a flat file export
 */
class FlatItemBuffer extends FileBuffer implements BufferInterface
{
    /** @var int */
    protected $count = 0;

    public function initilize($directory, string $writerType)
    {
        $this->count = 0;

        if (! $this->spreadsheet) {
            $filePath = $this->make($directory, $writerType);
        }

        $this->spreadsheet = SpoutWriterFactory::createSpreadSheet();

        return $filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function addData($items, $filePath, array $options = [])
    {
        $this->reopen($filePath, $filePath->getWriterType(), $options);

        $sheet = $this->spreadsheet->getActiveSheet();
        // Find the last row
        $this->highestRow = $sheet->getHighestRow();

        foreach ($items as $item) {
            $this->addToHeaders(array_keys($item));
            $this->highestRow++;
            // if (isset($options['withHeader']) && $options['withHeader']) {
            $this->setHeaders($sheet, $this->highestRow);
            // }

            $this->appendRows($item, $sheet);
            $this->count++;
        }

        $writer = SpoutWriterFactory::createWriter($filePath->getWriterType(), $this->spreadsheet, $options);
        $writer->save($filePath->getLocalPath());
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }
}
