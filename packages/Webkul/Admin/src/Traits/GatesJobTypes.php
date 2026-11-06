<?php

namespace Webkul\Admin\Traits;

use Webkul\DataTransfer\Services\JobTypeGate;

trait GatesJobTypes
{
    /**
     * The job types of a registry (`importers`, `exporters`, `quick_exporters`) whose module is switched on.
     *
     * @return array<array-key, mixed>
     */
    protected function visibleJobTypes(string $registry): array
    {
        return app(JobTypeGate::class)->visible($registry);
    }

    /**
     * The job type keys of a registry whose module is switched off.
     *
     * @return list<array-key>
     */
    protected function hiddenJobTypes(string $registry): array
    {
        return app(JobTypeGate::class)->hidden($registry);
    }

    /**
     * Respond 404 for a job instance or track whose entity type belongs to a switched-off module.
     */
    protected function abortIfHiddenJobType(mixed $entityType): void
    {
        abort_if(app(JobTypeGate::class)->hides($entityType), 404);
    }
}
