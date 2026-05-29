<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Linking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected mixed $import) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = JobLogger::make($this->import->id);

        $logger->info('Linking stage started.');

        app(ImportHelper::class)
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
