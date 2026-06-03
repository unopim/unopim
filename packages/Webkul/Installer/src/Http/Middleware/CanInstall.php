<?php

namespace Webkul\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Http\Controllers\InstallerController;

class CanInstall
{
    /**
     * Handles Requests for Installer middleware.
     *
     * @return void
     */
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->getPathInfo(), '/install')) {
            if ($this->isInstallationCompleted()) {
                return redirect()->route('admin.dashboard.index');
            }
        } else {
            if (! $this->isAlreadyInstalled()) {
                return redirect()->route('installer.index');
            }
        }

        return $next($request);
    }

    /**
     * Installation has been fully completed.
     *
     * Unlike {@see isAlreadyInstalled()}, this relies solely on the
     * `storage/installed` marker, which is written only at the true end of the
     * install flow ({@see InstallerController::adminConfigSetup()} when no demo
     * data is requested, otherwise {@see InstallerController::seedSampleData()}).
     * A populated `admins` table is not enough: the seeder inserts the default
     * admin (id 1) *before* those steps run, so gating on the DB would lock the
     * installer out mid-flow.
     */
    public function isInstallationCompleted(): bool
    {
        return file_exists(storage_path('installed'));
    }

    /**
     * Application Already Installed.
     *
     * @return bool
     */
    public function isAlreadyInstalled()
    {
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        return app(DatabaseManager::class)->isInstalled();
    }
}
