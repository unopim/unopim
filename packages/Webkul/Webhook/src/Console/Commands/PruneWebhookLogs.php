<?php

namespace Webkul\Webhook\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Description('Delete webhook delivery logs older than the configured retention window')]
#[Signature('webhook:logs:prune {--days= : Override the configured retention window}')]
class PruneWebhookLogs extends Command
{
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('webhook.retention_days', 30));

        if ($days <= 0) {
            $this->info(trans('webhook::app.webhooks.prune.disabled'));

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        do {
            $ids = DB::table('webhook_logs')
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += DB::table('webhook_logs')->whereIn('id', $ids)->delete();
        } while (true);

        $this->info(trans('webhook::app.webhooks.prune.done', ['count' => $deleted, 'days' => $days]));

        return self::SUCCESS;
    }
}
