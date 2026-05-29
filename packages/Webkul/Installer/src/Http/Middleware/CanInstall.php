<?php

namespace Webkul\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Installer\Helpers\DatabaseManager;

class CanInstall
{
    /**
     * Handles Requests for Installer middleware.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Str::contains($request->getPathInfo(), '/install')) {
            if ($this->isAlreadyInstalled() && ! $request->ajax()) {
                // Previously this branch unlinked `public/install.php` (the
                // pre-Laravel composer bootstrap) on every "installed app
                // visited /install" hit. That broke re-install workflows
                // (DB driver switch, demo-data reseed, CI feature tests)
                // and added no real security — the bootstrap is idempotent
                // once `vendor/` exists. Redirect-only is enough.
                return redirect()->route('admin.dashboard.index');
            }
        } elseif (! $this->isAlreadyInstalled()) {
            return redirect()->route('installer.index');
        }

        return $next($request);
    }

    /**
     * Application Already Installed.
     */
    public function isAlreadyInstalled(): bool
    {
        if (file_exists(storage_path('installed'))) {
            return true;
        }

        if (app(DatabaseManager::class)->isInstalled()) {
            touch(storage_path('installed'));

            Event::dispatch('unopim.installed');

            return true;
        }

        return false;
    }
}
