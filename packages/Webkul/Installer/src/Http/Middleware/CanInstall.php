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
                return to_route('admin.dashboard.index');
            }
        } elseif (! $this->isAlreadyInstalled()) {
            return to_route('installer.index');
        }

        return $next($request);
    }

    /**
     * Installation has been fully completed.
     *
     * Considered complete when either the `storage/installed` marker exists or
     * the persistent `installer.installed` DB flag is set — both written only at
     * the true end of the install flow ({@see InstallerController::adminConfigSetup()},
     * or {@see InstallerController::seedSampleData()} when demo data is requested).
     * The DB flag seals the installer even if the ephemeral marker is lost, while
     * a merely populated `admins` table is intentionally not enough: the seeder
     * inserts the default admin (id 1) *before* those steps run.
     */
    public function isInstallationCompleted(): bool
    {
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        return resolve(DatabaseManager::class)->isMarkedInstalled();
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

        return resolve(DatabaseManager::class)->isInstalled();
    }
}
