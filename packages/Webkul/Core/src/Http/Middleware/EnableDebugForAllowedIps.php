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
        if ($this->shouldEnableDebug($request)) {
            config(['app.debug' => true]);
            config(['debugbar.enabled' => true]);

            if (class_exists(Debugbar::class)) {
                // enable() also calls boot() internally, which is required when
                // DEBUGBAR_ENABLED=false prevented the ServiceProvider from
                // booting the bar at startup.
                app('debugbar')->enable();
            }
        } else {
            config(['debugbar.enabled' => false]);

            if (class_exists(Debugbar::class)) {
                Debugbar::disable();
            }
        }

        return $next($request);
    }

    /**
     * Whether IP-based debugging is enabled and the request IP is allow-listed.
     */
    protected function shouldEnableDebug(Request $request): bool
    {
        if (! core()->getConfigData('general.debug.settings.enabled')) {
            return false;
        }

        $allowedIps = array_filter(array_map(
            'trim',
            explode(',', (string) core()->getConfigData('general.debug.settings.allowed_ips'))
        ));

        return in_array($this->resolveClientIp($request), $allowedIps, true);
    }

    /**
     * Resolve the effective client IP.
     *
     * Forwarded headers (X-Forwarded-For / X-Real-IP) are checked first so
     * that proxy setups where every REMOTE_ADDR is 127.0.0.1 do not match
     * an allowlist entry of "127.0.0.1" for every user.  The raw request IP
     * is used only when no forwarded header is present (i.e. a direct
     * connection from localhost).
     */
    protected function resolveClientIp(Request $request): string
    {
        foreach ($this->forwardedCandidates($request) as $candidateIp) {
            if (filter_var($candidateIp, FILTER_VALIDATE_IP)) {
                return $candidateIp;
            }
        }

        return (string) $request->ip();
    }

    /**
     * Candidate forwarded IPs in priority order.
     *
     * @return array<int, string>
     */
    protected function forwardedCandidates(Request $request): array
    {
        $candidates = [];

        $xForwardedFor = (string) $request->headers->get('x-forwarded-for', '');

        foreach (array_filter(array_map('trim', explode(',', $xForwardedFor))) as $ip) {
            $candidates[] = $ip;
        }

        $xRealIp = (string) $request->headers->get('x-real-ip', '');

        if ($xRealIp !== '') {
            $candidates[] = trim($xRealIp);
        }

        return $candidates;
    }

    /**
     * Validate an IP and reject loopback values.
     */
    protected function isValidNonLoopbackIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return ! in_array($ip, ['127.0.0.1', '::1'], true);
    }
}
