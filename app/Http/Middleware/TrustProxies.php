<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Resolved at runtime from the TRUSTED_PROXIES env var (comma-separated
     * IPs or CIDRs). Falls back to the loopback address when unset so
     * production deployments behind a load balancer must opt in explicitly.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    public function __construct()
    {
        $value = (string) env('TRUSTED_PROXIES', '127.0.0.1');

        if ($value === '*') {
            $this->proxies = '*';

            return;
        }

        $this->proxies = array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
