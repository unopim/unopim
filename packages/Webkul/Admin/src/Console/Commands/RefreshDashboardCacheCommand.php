<?php

namespace Webkul\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Helpers\Dashboard;

class RefreshDashboardCacheCommand extends Command
{
    protected $signature = 'unopim:dashboard:refresh';

    protected $description = 'Refresh the dashboard statistics cache so the next page load sees fresh data.';

    public function handle(Dashboard $dashboardHelper): int
    {
        $keys = [
            'dashboard.total_catalogs',
            'dashboard.total_configurations',
            'dashboard.product_stats',
            'dashboard.needs_attention',
            'dashboard.channel_readiness',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        $this->info('Dashboard cache cleared. Fresh data will be loaded on next request.');

        return Command::SUCCESS;
    }
}
