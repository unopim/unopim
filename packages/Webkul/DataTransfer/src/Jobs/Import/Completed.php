<?php

namespace Webkul\DataTransfer\Jobs\Import;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\Tenant\Jobs\TenantAwareJob;

class Completed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     * @return void
     */
    public function __construct(protected $import, protected $jobTrackId)
    {
        $this->captureTenantContext();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(ImportHelper::class)
            ->setImport($this->import)
            ->setLogger(JobLogger::make($this->jobTrackId))
            ->completed();
    }
}
