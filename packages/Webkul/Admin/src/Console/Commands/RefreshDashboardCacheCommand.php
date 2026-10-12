<?php

namespace Webkul\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\Dashboard;

/**
 * Recomputes the dashboard aggregates off the request path. Clearing the keys
 * without warming them hands a catalog-wide scan to the next page load, and
 * every request arriving during it starts another copy of the same scan.
 */
class RefreshDashboardCacheCommand extends Command
{
    protected $signature = 'unopim:dashboard:refresh';

    protected $description = 'Recompute the dashboard statistics cache so page loads never pay for a catalog-wide scan.';

    public function handle(Dashboard $dashboardHelper): int
    {
        $warmers = [
            'dashboard.total_catalogs'       => fn () => $dashboardHelper->getTotalCatalogs(),
            'dashboard.total_configurations' => fn () => $dashboardHelper->getTotalConfigurations(),
            'dashboard.product_stats'        => fn () => $dashboardHelper->getProductStats(),
            'dashboard.needs_attention'      => fn () => $dashboardHelper->getNeedsAttention(),
            'dashboard.channel_readiness'    => fn () => $dashboardHelper->getChannelReadiness(),
        ];

        $failed = 0;

        foreach ($warmers as $key => $warmer) {
            Cache::forget($key);

            $started = microtime(true);

            try {
                $warmer();

                $this->components->twoColumnDetail($key, round(microtime(true) - $started, 2).'s');
            } catch (\Throwable $e) {
                $failed++;

                $this->components->twoColumnDetail($key, '<fg=red>failed</>');

                $this->components->error($key.': '.$e->getMessage());
            }
        }

        if ($failed > 0) {
            $this->components->warn($failed.' dashboard entry/entries could not be recomputed and remain cold.');

            return self::FAILURE;
        }

        $this->components->info('Dashboard cache warmed.');

        return self::SUCCESS;
    }
}
