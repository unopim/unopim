<?php

namespace Webkul\DataTransfer\Queue;

use Illuminate\Queue\Worker as BaseWorker;
use Illuminate\Queue\WorkerOptions;
use ReflectionMethod;

class Worker extends BaseWorker
{
    protected ?int $timeoutHandlerArity = null;

    /**
     * Listen to the given queue in a loop.
     */
    public function singleJobDaemon(string $connectionName, string $queue, WorkerOptions $options): void
    {
        if ($supportsAsyncSignals = $this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            if ($this->resetScope !== null) {
                ($this->resetScope)();
            }

            $job = $this->getNextJob(
                $this->manager->connection($connectionName), $queue
            );

            if ($supportsAsyncSignals) {
                $this->registerJobTimeoutHandler($connectionName, $queue, $job, $options);
            }

            try {
                if (! $job) {
                    break;
                }

                $this->runJob($job, $connectionName, $options);

                if ($options->rest > 0) {
                    $this->sleep($options->rest);
                }
            } finally {
                if ($supportsAsyncSignals) {
                    $this->resetTimeoutHandler();
                }
            }
        }
    }

    /**
     * Register the timeout handler against whichever framework signature is installed.
     *
     * Laravel 13.32 prepended $connectionName and $queue to ($job, $options), so the trailing arguments always match.
     */
    protected function registerJobTimeoutHandler(string $connectionName, string $queue, $job, WorkerOptions $options): void
    {
        $this->timeoutHandlerArity ??= new ReflectionMethod(parent::class, 'registerTimeoutHandler')->getNumberOfParameters();

        $this->registerTimeoutHandler(
            ...array_slice([$connectionName, $queue, $job, $options], -$this->timeoutHandlerArity)
        );
    }
}
