<?php

namespace Webkul\DataTransfer\Jobs\Export\File;

use OpenSpout\Common\Entity\Row;
use Webkul\DataTransfer\Buffer\BufferInterface;
use Webkul\DataTransfer\Buffer\FileBuffer;

/**
 * Puts items into a buffer and calculate headers during a flat file export
 */
class FlatItemBuffer extends FileBuffer implements BufferInterface
{
    /** @var int */
    protected $count = 0;

    protected $headerWritten = false;

    public function initialize($directory, $fileName, $options = [])
    {
        $this->count = 0;

        $this->headerWritten = false;

        if (! $this->writer) {
            $this->filePath = $this->make($directory, $options['type'], $fileName);

            $this->writer = $this->getWriter($this->filePath, $options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addData($items)
    {
        foreach ($items as $item) {
            if (! $this->headerWritten) {
                $headers = array_keys($item);
                $this->writeHeader($headers);
                $this->headerWritten = true;
            }

            $this->writer->addRow($this->escapeFormulaCells(Row::fromValues($item)));
            $this->count++;
        }
    }

    public function writerClose()
    {
        $this->writer->close();
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }
}
