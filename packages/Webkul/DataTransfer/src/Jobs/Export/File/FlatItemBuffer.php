<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use Webkul\DataTransfer\Buffer\BufferInterface;
use Webkul\DataTransfer\Buffer\FileBuffer;
use OpenSpout\Common\Entity\Row;

/**
 * Puts items into a buffer and calculate headers during a flat file export
 */
class FlatItemBuffer extends FileBuffer implements BufferInterface
{
    /** @var int */
    protected $count = 0;

    protected $headerWritten = false;

    public function initilize($directory, string $writerType, ?string $fileName = null)
    {
        $this->count = 0;

        $this->headerWritten = false;

        if (! $this->spreadsheet) {
            $filePath = $this->make($directory, $writerType, $fileName);
        }

        return $filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function addData($items, $filePath, array $options = [])
    {
        $options['type'] = $filePath->getWriterType();
        if (! $this->writer) {
            $this->writer = $this->getWriter($filePath, $options);
        }
        
        foreach ($items as $item) {
            if (! $this->headerWritten) {
                $headers = array_keys($item);
                $this->writeHeader($headers);
                $this->headerWritten = true;
            }

            $this->writer->addRow($this->escapeFormulaCells(Row::fromValues($item)));
            $this->count++;
        }

        $this->writer->close();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }
}
