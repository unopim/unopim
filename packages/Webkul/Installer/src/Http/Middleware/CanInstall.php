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
            // Once installation is *complete*, the installer surface must be
            // sealed for everyone — including XHR/AJAX requests. A previous
            // `! $request->ajax()` exception here let an unauthenticated
            // attacker re-trigger `install/api/admin-config-setup` (which
            // overwrites admin id 1) simply by sending an
            // `X-Requested-With: XMLHttpRequest` header. The live installer
            // UI never trips this branch because the completion marker is
            // only written by the final SMTP step, after every api call.
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
     * `storage/installed` marker, which is written only by the final
     * installer step ({@see InstallerController::smtpConfigSetup()}).
     * A populated `admins` table is not enough: the seeder inserts the
     * default admin (id 1) *before* the admin-config and SMTP steps run, so
     * gating on the DB would lock the installer out mid-flow.
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

        // Report installed state without writing the completion marker. The
        // marker is owned exclusively by the final installer step so that
        // `isInstallationCompleted()` cannot fire mid-install — the seeder
        // populates the `admins` table several steps before the install
        // actually finishes, and a premature marker here used to seal the
        // installer before the admin-config/SMTP steps could run.
        return app(DatabaseManager::class)->isInstalled();
    }
}
