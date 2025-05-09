<?php

namespace Webkul\DataTransfer\Jobs\Export;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Services\JobLogger;

class Completed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  mixed  $import
     * @return void
     */
    public function __construct(
        protected $export,
        protected $jobTrackId,
        protected $filePath
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(ExportHelper::class)
            ->setExport($this->export)
            ->setLogger(JobLogger::make($this->jobTrackId))
            ->flush($this->filePath)
            ->completed();
    }
}
