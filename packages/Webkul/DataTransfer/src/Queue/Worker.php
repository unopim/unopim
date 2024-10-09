<?php

namespace Webkul\DataTransfer\Queue;

use Illuminate\Queue\Worker as BaseWorker;
use Illuminate\Queue\WorkerOptions;

class Worker extends BaseWorker
{
    /**
     * Listen to the given queue in a loop.
     */
    public function singleJobDaemon(string $connectionName, string $queue, WorkerOptions $options): void
    {
        if ($supportsAsyncSignals = $this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {
            if (isset($this->resetScope)) {
                ($this->resetScope)();
            }

            // First, we will attempt to get the next job off of the queue. We will also
            // register the timeout handler and reset the alarm for this job so it is
            // not stuck in a frozen state forever. Then, we can fire off this job.
            $job = $this->getNextJob(
                $this->manager->connection($connectionName), $queue
            );

            if ($supportsAsyncSignals) {
                $this->registerTimeoutHandler($job, $options);
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            if ($job) {
                $this->runJob($job, $connectionName, $options);

                if ($options->rest > 0) {
                    $this->sleep($options->rest);
                }
            } else {
                break;
            }

            if ($supportsAsyncSignals) {
                $this->resetTimeoutHandler();
            }
        }
    }
}
