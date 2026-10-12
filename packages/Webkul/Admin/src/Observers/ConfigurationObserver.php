<?php

namespace Webkul\Admin\Observers;

use Illuminate\Database\Eloquent\Model;
use Webkul\Admin\Helpers\Dashboard;

/**
 * Shared observer for configuration entities whose totals are shown on the
 * dashboard (attributes, attribute groups, attribute families, locales,
 * channels, currencies). Any create/update/delete busts the cached totals so
 * the dashboard updates immediately.
 */
class ConfigurationObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        Dashboard::invalidateConfigurationCache();
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        Dashboard::invalidateConfigurationCache();
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        Dashboard::invalidateConfigurationCache();
    }
}
