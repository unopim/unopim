<?php

namespace Webkul\DataTransfer\Queue;

use Illuminate\Queue\Worker as BaseWorker;
use Illuminate\Queue\WorkerOptions;
use ReflectionMethod;

class Worker extends BaseWorker
{
    protected ?bool $timeoutHandlerTakesQueueContext = null;

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
     */
    protected function registerJobTimeoutHandler(string $connectionName, string $queue, $job, WorkerOptions $options): void
    {
        $this->timeoutHandlerTakesQueueContext ??= new ReflectionMethod(parent::class, 'registerTimeoutHandler')->getNumberOfParameters() >= 4;

        $arguments = $this->timeoutHandlerTakesQueueContext
            ? [$connectionName, $queue, $job, $options]
            : [$job, $options];

        $this->registerTimeoutHandler(...$arguments);
    }
}
