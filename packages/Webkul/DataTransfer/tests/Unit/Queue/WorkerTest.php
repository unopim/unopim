<?php

use Illuminate\Queue\Worker as BaseWorker;
use Illuminate\Queue\WorkerOptions;
use Webkul\DataTransfer\Queue\Worker;

/*
 * Guards against framework signature drift: Illuminate\Queue\Worker::registerTimeoutHandler()
 * takes ($job, $options) up to laravel/framework 13.30 and ($connectionName, $queue, $job,
 * $options) from 13.32. composer.json allows both, so a fixed argument list raises an
 * ArgumentCountError on half of the supported range and aborts every queued job.
 */
it('passes the timeout handler the argument list the installed framework declares', function () {
    $worker = new class extends Worker
    {
        public array $captured = [];

        public function __construct() {}

        public function register($job, WorkerOptions $options): void
        {
            $this->registerJobTimeoutHandler('database', 'default', $job, $options);
        }

        protected function registerTimeoutHandler(...$arguments)
        {
            $this->captured = $arguments;
        }
    };

    $options = new WorkerOptions;

    $worker->register(null, $options);

    $captured = $worker->captured;

    expect($captured)->toHaveCount(
        (new ReflectionMethod(BaseWorker::class, 'registerTimeoutHandler'))->getNumberOfParameters()
    );

    expect(end($captured))->toBe($options);
});
