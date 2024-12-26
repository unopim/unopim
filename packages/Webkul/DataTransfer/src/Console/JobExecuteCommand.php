<?php

namespace Webkul\DataTransfer\Console;

use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobInstances;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Jobs\Export\ExportTrackBatch;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Queue\Worker;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\User\Repositories\AdminRepository;

use function Termwind\terminal;

class JobExecuteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:queue:work 
                            {jobId} 
                            {userEmailId}
                            {connection? : The name of the queue connection to work}
                            {--queue= : The names of the queue to work}
                            {--name=single : The name of the worker}
                            {--delay=0 : The number of seconds to delay failed jobs (Deprecated)}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--memory=128 : The memory limit in megabytes}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start processing jobs on the queue as a daemon by job id';

    /**
     * Holds the start time of the last processed job, if any.
     *
     * @var float|null
     */
    protected $latestStartedAt;

    public function __construct(
        protected Worker $worker,
        protected Cache $cache,
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected AdminRepository $adminRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jobId = $this->argument('jobId');
        $userId = null;

        if ($this->argument('userEmailId')) {
            $user = $this->adminRepository->findByField('email', $this->argument('userEmailId'))->first();
            if (! $user) {
                $this->error('User not found given user email Id.');

                return 1;
            }

            $userId = $user->id;
        }

        $jobInstance = $this->jobInstancesRepository->find($jobId);
        if (! $jobInstance) {
            $this->error('Job not found given jobId.');

            return 1;
        }

        try {
            $queueName = $this->dispatchJob($jobInstance, $userId);

            $this->listenForEvents();

            $connectionName = $this->argument('connection')
                ?: $this->laravel['config']['queue.default'];

            $this->runWorker(
                $connectionName, $queueName
            );

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    /**
     * Run the worker instance.
     */
    protected function runWorker(string $connection, string $queue): void
    {
        $this->worker
            ->setName($this->option('name'))
            ->setCache($this->cache)
            ->singleJobDaemon(
                $connection, $queue, $this->gatherWorkerOptions()
            );
    }

    /**
     * Gather all of the queue worker options as a single object.
     */
    protected function gatherWorkerOptions(): WorkerOptions
    {
        return new WorkerOptions(
            $this->option('name'),
            max($this->option('backoff'), $this->option('delay')),
            $this->option('memory'),
            $this->option('timeout'),
            $this->option('tries'),
        );
    }

    /**
     * Dispatches a job for export or import based on the provided job instance and user ID.
     */
    protected function dispatchJob(JobInstances $jobInstance, ?string $userId): string
    {
        // Dispatch an event before the export process starts
        Event::dispatch('data_transfer.exports.export.now.before', $jobInstance);

        // Create a new job track instance in the database
        $jobTrackInstance = $this->jobTrackRepository->create([
            'state'                 => Export::STATE_PENDING,
            'validation_strategy'   => $jobInstance->validation_strategy,
            'allowed_errors'        => $jobInstance->allowed_errors,
            'field_separator'       => $jobInstance->field_separator,
            'file_path'             => $jobInstance->file_path,
            'images_directory_path' => $jobInstance->images_directory_path,
            'meta'                  => $jobInstance->toJson(),
            'job_instances_id'      => $jobInstance->id,
            'user_id'               => $userId,
            'created_at'            => now(),
            'updated_at'            => now(),
            'action'                => $jobInstance->action,
        ]);

        // Generate a queue name based on the job instance code, type, and job track ID
        $queue = $this->option('queue') ?: sprintf('%s-%s-%s', $this->generateQueueCode($jobInstance->code), $jobInstance->type, $jobTrackInstance->id);

        // Dispatch the appropriate job (ExportTrackBatch or ImportTrackBatch) to the generated queue
        if ($jobInstance->type == 'export') {
            ExportTrackBatch::dispatch($jobTrackInstance)->onQueue($queue);
        } else {
            ImportTrackBatch::dispatch($jobTrackInstance)->onQueue($queue);
        }

        // Log the start of the job processing
        $this->info(sprintf('Started processing job %s in queue %s', $jobInstance->code, $queue));

        return $queue;
    }

    /**
     * Generates a queue code from the given string by replacing non-alphanumeric characters with hyphens and trimming any leading or trailing hyphens.
     */
    protected function generateQueueCode(string $code): string
    {
        $convertedString = preg_replace('/[^a-zA-Z0-9]+/', '-', $code);

        // Optionally, trim any leading or trailing hyphens
        $convertedString = trim($convertedString, '-');

        return $convertedString;
    }

    /**
     * Listen for the queue events in order to update the console output.
     */
    protected function listenForEvents(): void
    {
        $this->laravel['events']->listen(JobProcessing::class, function ($event) {
            $this->writeOutput($event->job, 'starting');
        });

        $this->laravel['events']->listen(JobProcessed::class, function ($event) {
            $this->writeOutput($event->job, 'success');
        });

        $this->laravel['events']->listen(JobReleasedAfterException::class, function ($event) {
            $this->writeOutput($event->job, 'released_after_exception');
        });

        $this->laravel['events']->listen(JobFailed::class, function ($event) {
            $this->writeOutput($event->job, 'failed');

            $this->logFailedJob($event);
        });
    }

    /**
     * Write the status output for the queue worker.
     */
    protected function writeOutput(Job $job, string $status): void
    {
        $this->output->write(sprintf(
            '  <fg=gray>%s</> %s%s',
            $this->now()->format('Y-m-d H:i:s'),
            $job->resolveName(),
            $this->output->isVerbose()
            ? sprintf(' <fg=gray>%s</>', $job->getJobId())
            : ''
        ));

        if ($status == 'starting') {
            $this->latestStartedAt = microtime(true);

            $dots = max(terminal()->width() - mb_strlen($job->resolveName()) - (
                $this->output->isVerbose() ? (mb_strlen($job->getJobId()) + 1) : 0
            ) - 33, 0);

            $this->output->write(' '.str_repeat('<fg=gray>.</>', $dots));

            $this->output->writeln(' <fg=yellow;options=bold>RUNNING</>');

            return;
        }

        $runTime = $this->formatRunTime($this->latestStartedAt);

        $dots = max(terminal()->width() - mb_strlen($job->resolveName()) - (
            $this->output->isVerbose() ? (mb_strlen($job->getJobId()) + 1) : 0
        ) - mb_strlen($runTime) - 31, 0);

        $this->output->write(' '.str_repeat('<fg=gray>.</>', $dots));
        $this->output->write(" <fg=gray>$runTime</>");

        $this->output->writeln(match ($status) {
            'success'                  => ' <fg=green;options=bold>DONE</>',
            'released_after_exception' => ' <fg=yellow;options=bold>FAIL</>',
            default                    => ' <fg=red;options=bold>FAIL</>',
        });
    }

    /**
     * Store a failed job event.
     */
    protected function logFailedJob(JobFailed $event): void
    {
        $this->laravel['queue.failer']->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }

    /**
     * Given a start time, format the total run time for human readability.
     */
    protected function formatRunTime(float $startTime): string
    {
        $runTime = (microtime(true) - $startTime) * 1000;

        return $runTime > 1000
            ? CarbonInterval::milliseconds($runTime)->cascade()->forHumans(short: true)
            : number_format($runTime, 2).'ms';
    }

    /**
     * Get the current date / time.
     */
    protected function now(): Carbon
    {
        $queueTimezone = $this->laravel['config']->get('queue.output_timezone');

        if (
            $queueTimezone
            && $queueTimezone !== $this->laravel['config']->get('app.timezone')
        ) {
            return Carbon::now()->setTimezone($queueTimezone);
        }

        return Carbon::now();
    }
}
