<?php

namespace Webkul\ChannelConnector\ValueObjects;

class BatchSyncResult
{
    public function __construct(
        public readonly int $totalProcessed,
        public readonly int $successCount,
        public readonly int $failedCount,
        public readonly int $skippedCount,
        public readonly array $results = [],
        public readonly array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'total_processed' => $this->totalProcessed,
            'success_count'   => $this->successCount,
            'failed_count'    => $this->failedCount,
            'skipped_count'   => $this->skippedCount,
            'results'         => array_map(fn (SyncResult $r) => $r->toArray(), $this->results),
            'errors'          => $this->errors,
        ];
    }
}
