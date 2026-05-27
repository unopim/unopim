<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $hosts = [$this->allSubdomainsOfApplicationUrl()];

        foreach (array_filter(array_map('trim', explode(',', (string) env('TRUSTED_HOSTS', '')))) as $host) {
            $hosts[] = '^'.preg_quote($host, '/').'$';
        }

        return array_values(array_filter($hosts));
    }
}
