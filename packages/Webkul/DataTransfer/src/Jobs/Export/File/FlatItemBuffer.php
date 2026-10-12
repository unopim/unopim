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

    protected $writeHeaders = true;

    /**
     * Optional `columnKey => label` map used to write readable header labels instead of codes
     * when the "use_labels" option is enabled. Unmapped keys fall back to the key itself.
     *
     * @var array
     */
    protected $headerLabels = [];

    public function initialize($directory, ?string $fileName, array $options = []): static
    {
        $this->count = 0;

        $this->headerWritten = false;

        $this->writeHeaders = $options['writeHeaders'] ?? true;

        $this->headerLabels = $options['headerLabels'] ?? [];

        if (! $this->writer) {
            $this->filePath = $this->make($directory, $options['type'], $fileName);

            $this->writer = $this->getWriter($this->filePath, $options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addData($items): void
    {
        foreach ($items as $item) {
            if (! $this->headerWritten) {
                if ($this->writeHeaders) {
                    $this->writeHeader($this->buildHeaders(array_keys($item)));
                }

                $this->headerWritten = true;
            }

            $this->writer->addRow($this->escapeFormulaCells(Row::fromValues($item)));
            $this->count++;
        }
    }

    public function buildHeaders(array $keys): array
    {
        if (empty($this->headerLabels)) {
            return $keys;
        }

        return array_map(fn ($key) => $this->headerLabels[$key] ?? $key, $keys);
    }

    public function writerClose(): void
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
