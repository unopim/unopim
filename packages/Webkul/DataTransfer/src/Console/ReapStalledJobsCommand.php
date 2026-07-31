<?php

namespace Webkul\DataTransfer\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\DataTransfer\Services\JobHealth;

#[Description('Fail import/export jobs whose worker died without reporting back')]
#[Signature('unopim:data-transfer:reap-stalled')]
class ReapStalledJobsCommand extends Command
{
    public function handle(JobHealth $jobHealth): int
    {
        $reaped = $jobHealth->reap();

        $this->info(trans('data_transfer::app.job.reaped', ['count' => $reaped]));

        return self::SUCCESS;
    }
}
