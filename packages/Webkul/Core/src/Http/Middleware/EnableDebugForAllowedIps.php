<?php

namespace Webkul\Core\Http\Middleware;

use Barryvdh\Debugbar\Facades\Debugbar;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableDebugForAllowedIps
{
    /**
     * Turn on debug output (and the debug bar) for the current request when the
     * admin has enabled IP-based debugging and the caller's IP is allow-listed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $featureEnabled = (bool) core()->getConfigData('general.debug.settings.enabled');

        if ($featureEnabled && $this->isAllowedIp($request) && $this->isAllowedByEnvironment($request)) {
            config(['app.debug' => true]);
            config(['debugbar.enabled' => true]);

            if (class_exists(Debugbar::class)) {
                // enable() also calls boot() internally, which is required when
                // DEBUGBAR_ENABLED=false prevented the ServiceProvider from
                // booting the bar at startup.
                resolve('debugbar')->enable();
            }

            return $next($request);
        }

        /*
         * Not enabling debug for this request. Force app.debug back off only when
         * the feature is enabled — under Octane the worker is long-lived, so a
         * previous allow-listed request on the same worker may have flipped it on,
         * and leaving it on would leak stack traces to every later (including
         * unauthenticated) request. When the feature is off the middleware stays
         * inert and never touches app.debug.
         */
        if ($featureEnabled) {
            config(['app.debug' => false]);
        }

        config(['debugbar.enabled' => false]);

        if (class_exists(Debugbar::class)) {
            Debugbar::disable();
        }

        return $next($request);
    }

    /**
     * Whether the request IP is on the configured allow-list.
     *
     * The client IP comes from Request::ip(), which honours the application's
     * trusted-proxy configuration: forwarded headers (X-Forwarded-For) are read
     * only when the request arrives from a TRUSTED_PROXIES address. A client that
     * is not a trusted proxy cannot spoof its way onto the allow-list by sending
     * its own X-Forwarded-For, so enabling debug (and leaking stack traces / the
     * debug bar) stays restricted to the configured IPs.
     */
    protected function isAllowedIp(Request $request): bool
    {
        return $this->matchesList(
            $request,
            (string) core()->getConfigData('general.debug.settings.allowed_ips')
        );
    }

    /**
     * Whether the deployment-level `APP_DEBUG_ALLOWED_IPS` allow-list admits the
     * request. An unset list imposes no restriction, so hosts that do not pin the
     * value keep relying on the database-backed allow-list alone.
     */
    protected function isAllowedByEnvironment(Request $request): bool
    {
        $allowedIps = (string) config('app.debug_allowed_ips');

        return $allowedIps === '' || $this->matchesList($request, $allowedIps);
    }

    /**
     * Match the resolved client IP against a comma-separated allow-list.
     */
    protected function matchesList(Request $request, string $allowedIps): bool
    {
        return in_array((string) $request->ip(), array_filter(array_map(
            trim(...),
            explode(',', $allowedIps)
        )), true);
    }
}
