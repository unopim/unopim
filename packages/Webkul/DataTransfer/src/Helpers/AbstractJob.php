<?php

namespace Webkul\DataTransfer\Helpers;

use Psr\Log\LoggerInterface;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;

abstract class AbstractJob
{
    public const STATE_PENDING = 'pending';

    public const STATE_VALIDATED = 'validated';

    public const STATE_PROCESSING = 'processing';

    public const STATE_PROCESSED = 'processed';

    public const STATE_LINKING = 'linking';

    public const STATE_LINKED = 'linked';

    public const STATE_INDEXING = 'indexing';

    public const STATE_INDEXED = 'indexed';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    public const VALIDATION_STRATEGY_SKIP_ERRORS = 'skip-errors';

    public const VALIDATION_STRATEGY_STOP_ON_ERROR = 'stop-on-errors';

    public const ACTION_APPEND = 'append';

    public const ACTION_DELETE = 'delete';

    /**
     * JobTrack instance.
     */
    protected JobTrackContract $job;

    /**
     * Error helper instance.
     */
    protected Error $errorHelper;

    /**
     * Job-specific logger.
     */
    protected LoggerInterface $jobLogger;

    /**
     * Repository instances.
     */
    protected JobTrackRepository $jobTrackRepository;

    protected JobTrackBatchRepository $jobTrackBatchRepository;

    public function __construct(
        JobTrackRepository $jobTrackRepository,
        JobTrackBatchRepository $jobTrackBatchRepository,
        Error $errorHelper
    ) {
        $this->jobTrackRepository = $jobTrackRepository;
        $this->jobTrackBatchRepository = $jobTrackBatchRepository;
        $this->errorHelper = $errorHelper;
    }

    public function setJob(JobTrackContract $job): self
    {
        $this->job = $job;

        return $this;
    }

    public function getJob(): JobTrackContract
    {
        return $this->job;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->jobLogger = $logger;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->jobLogger;
    }

    public function getErrorHelper(): Error
    {
        return $this->errorHelper;
    }

    public function stateUpdate(string $state): self
    {
        $updated = $this->jobTrackRepository->update(['state' => $state], $this->job->id);
        $this->setJob($updated);

        return $this;
    }

    public function isValid(): bool
    {
        return $this->job->state !== self::STATE_FAILED;
    }

    abstract public function start(?object $batch = null, ?string $queue = null): bool;

    abstract public function completed(): void;
}
