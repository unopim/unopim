<?php

namespace Webkul\ChannelConnector\Console\Commands;

use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Services\SyncJobManager;

class RunScheduledSyncsCommand extends Command
{
    protected $signature = 'channel-connector:run-scheduled-syncs
                            {--dry-run : Only log what would be dispatched without actually dispatching}';

    protected $description = 'Run scheduled channel connector syncs';

    /**
     * Predefined frequency-to-cron mappings.
     */
    protected array $frequencyMap = [
        'hourly'  => '0 * * * *',
        'daily'   => '0 0 * * *',
        'weekly'  => '0 0 * * 0',
    ];

    public function handle(SyncJobManager $syncJobManager): int
    {
        $isDryRun = $this->option('dry-run');
        $now = Carbon::now();

        if ($isDryRun) {
            $this->info('[DRY RUN] No syncs will actually be dispatched.');
        }

        $connectors = ChannelConnector::where('status', 'connected')->get();

        if ($connectors->isEmpty()) {
            $this->info('No connected channel connectors found.');

            return self::SUCCESS;
        }

        $this->info("Found {$connectors->count()} connected connector(s). Evaluating schedules...");

        $dispatched = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($connectors as $connector) {
            try {
                $result = $this->processConnector($connector, $syncJobManager, $now, $isDryRun);

                if ($result) {
                    $dispatched++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++;

                $this->error("Connector [{$connector->code}] (ID: {$connector->id}) failed: {$e->getMessage()}");

                Log::error('[ChannelConnector] Scheduled sync failed for connector', [
                    'connector_id'   => $connector->id,
                    'connector_code' => $connector->code,
                    'error'          => $e->getMessage(),
                    'trace'          => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Scheduled sync run complete: {$dispatched} dispatched, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single connector's schedule.
     *
     * Returns true if a sync was dispatched (or would be in dry-run), false if skipped.
     */
    protected function processConnector(
        ChannelConnector $connector,
        SyncJobManager $syncJobManager,
        Carbon $now,
        bool $isDryRun,
    ): bool {
        $schedule = $connector->sync_schedule;

        if (empty($schedule) || empty($schedule['enabled'])) {
            $this->line("  [{$connector->code}] Schedule not enabled -- skipped.");

            return false;
        }

        $cronExpression = $this->resolveCronExpression($schedule);

        if ($cronExpression === null) {
            $this->warn("  [{$connector->code}] Invalid frequency '{$schedule['frequency']}' with no cron_expression -- skipped.");

            return false;
        }

        $cron = new CronExpression($cronExpression);
        $lastScheduledAt = ! empty($schedule['last_scheduled_at'])
            ? Carbon::parse($schedule['last_scheduled_at'])
            : null;

        $previousRunTime = Carbon::instance($cron->getPreviousRunDate($now, 0, true));

        if ($lastScheduledAt !== null && $lastScheduledAt->gte($previousRunTime)) {
            $this->line("  [{$connector->code}] Not due yet (last: {$lastScheduledAt}, prev cron: {$previousRunTime}) -- skipped.");

            return false;
        }

        $syncType = $schedule['sync_type'] ?? 'incremental';

        if ($isDryRun) {
            $this->info("  [{$connector->code}] [DRY RUN] Would dispatch '{$syncType}' sync (cron: {$cronExpression}).");

            Log::info('[ChannelConnector] [DRY RUN] Scheduled sync would be dispatched', [
                'connector_id'    => $connector->id,
                'connector_code'  => $connector->code,
                'sync_type'       => $syncType,
                'cron_expression' => $cronExpression,
            ]);

            return true;
        }

        $syncJob = $syncJobManager->triggerSync($connector, $syncType);

        // Update last_scheduled_at in the sync_schedule JSON
        $updatedSchedule = $schedule;
        $updatedSchedule['last_scheduled_at'] = $now->toIso8601String();
        $connector->update(['sync_schedule' => $updatedSchedule]);

        $this->info("  [{$connector->code}] Dispatched '{$syncType}' sync (job ID: {$syncJob->job_id}, cron: {$cronExpression}).");

        Log::info('[ChannelConnector] Scheduled sync dispatched', [
            'connector_id'    => $connector->id,
            'connector_code'  => $connector->code,
            'sync_job_id'     => $syncJob->id,
            'sync_type'       => $syncType,
            'cron_expression' => $cronExpression,
        ]);

        return true;
    }

    /**
     * Resolve the cron expression from the schedule's frequency field.
     */
    protected function resolveCronExpression(array $schedule): ?string
    {
        $frequency = $schedule['frequency'] ?? null;

        if ($frequency === null) {
            return null;
        }

        if ($frequency === 'custom') {
            return $schedule['cron_expression'] ?? null;
        }

        return $this->frequencyMap[$frequency] ?? null;
    }
}
