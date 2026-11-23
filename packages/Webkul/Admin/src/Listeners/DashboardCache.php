<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Helpers\Dashboard;

class DashboardCache
{
    /**
     * Drop the cached dashboard figures after a bulk write.
     */
    public function flush(): void
    {
        Dashboard::invalidateAllCache();
    }

    /**
     * Drop only the product figures after a completeness recalculation.
     */
    public function flushProducts(): void
    {
        Dashboard::invalidateProductCache();
    }
}
