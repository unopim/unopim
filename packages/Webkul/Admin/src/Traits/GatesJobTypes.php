<?php

namespace Webkul\Admin\Traits;

use Webkul\DataTransfer\Contracts\JobInstances;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
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

    /**
     * Find a job instance, responding 404 when it is missing or its module is switched off.
     */
    protected function findVisibleJobInstance(int|string $id): JobInstances
    {
        $jobInstance = app(JobInstancesRepository::class)->findOrFail($id);

        $this->abortIfHiddenJobType($jobInstance->entity_type);

        return $jobInstance;
    }

    /**
     * Find a job track, responding 404 when it is missing or its job instance belongs to a switched-off module.
     */
    protected function findVisibleJobTrack(int|string $id): JobTrack
    {
        $jobTrack = app(JobTrackRepository::class)->findOrFail($id);

        $this->abortIfHiddenJobType($jobTrack->jobInstance?->entity_type);

        return $jobTrack;
    }
}
