<?php

namespace Webkul\DataTransfer\Tests\Support;

use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;

class LinkingTestImporter extends FilelessTestImporter
{
    protected bool $linkingRequired = true;

    /**
     * @var array<int, int>
     */
    public array $linkedBatchIds = [];

    public function linkBatch(JobTrackBatchContract $batch): bool
    {
        $this->linkedBatchIds[] = $batch->id;

        return true;
    }
}
