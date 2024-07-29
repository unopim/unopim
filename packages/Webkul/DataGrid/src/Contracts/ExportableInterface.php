<?php

namespace Webkul\DataGrid\Contracts;

interface ExportableInterface
{
    /**
     * Return formatted rows of data which can be used for exporting the data to a file
     */
    public function getExportableData(array $parameters = []);
}
