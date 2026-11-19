<?php

namespace Webkul\DataTransfer\Contracts\Exporters;

/**
 * An exporter that writes its own export file instead of the flat CSV/XLSX writer, for formats such as JSON Lines.
 */
interface WritesExportFile
{
    /**
     * Write the buffered rows of every batch to the export file and return its path on the private disk.
     *
     * @param  iterable<int, mixed>  $exportBuffer
     */
    public function writeExportFile(iterable $exportBuffer): string;
}
