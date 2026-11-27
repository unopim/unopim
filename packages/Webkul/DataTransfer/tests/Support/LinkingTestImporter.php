<?php

namespace Webkul\DataTransfer\Tests\Support;

use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;

class LinkingTestImporter extends FilelessTestImporter
{
    protected bool $linkingRequired = true;

    /**
     * Ids of the batches handed to linkBatch().
     *
     * @var array<int, int>
     */
    public array $linkedBatchIds = [];

    /**
     * Record the batch handed to the link step.
     */
    public function linkBatch(JobTrackBatchContract $batch): bool
    {
        $this->linkedBatchIds[] = $batch->id;

        return true;
    }
}
