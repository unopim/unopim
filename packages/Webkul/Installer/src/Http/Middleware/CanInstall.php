<?php

namespace Webkul\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Installer\Helpers\DatabaseManager;

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
                if (file_exists(realpath(__DIR__.'/../../../../../../public/install.php'))) {
                    unlink(realpath(__DIR__.'/../../../../../../public/install.php'));
                }

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
     */
    public function isInstallationCompleted(): bool
    {
        return file_exists((config('installer.installed_marker') ?? storage_path('installed')));
    }

    /**
     * Application Already Installed.
     *
     * @return bool
     */
    public function isAlreadyInstalled()
    {
        if (file_exists((config('installer.installed_marker') ?? storage_path('installed')))) {
            return true;
        }

        return app(DatabaseManager::class)->isInstalled();
    }
}
