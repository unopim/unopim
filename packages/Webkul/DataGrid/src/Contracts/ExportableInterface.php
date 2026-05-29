<?php

declare(strict_types=1);

namespace Webkul\DataGrid\Contracts;

use Illuminate\Support\Collection;

interface ExportableInterface
{
    /**
     * Return formatted rows of data which can be used for exporting the data to a file
     */
    public function getExportableData(array $parameters = []): array|Collection;
}
