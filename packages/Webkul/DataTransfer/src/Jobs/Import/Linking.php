<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Linking implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     */
    public function __construct(protected $import) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = JobLogger::make($this->import->id);

        $logger->info('Linking stage started.');

        resolve(ImportHelper::class)
            ->setImport($this->import)
            ->setLogger($logger)
            ->linking();
    }

    public function failed(\Throwable $exception): void
    {
        JobLogger::make($this->import->id)->error("Linking stage failed: {$exception->getMessage()}", [
            'exception' => $exception->getTraceAsString(),
        ]);
    }
}
