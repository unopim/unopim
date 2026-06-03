<?php

namespace Webkul\AppUrlGuard\Concerns;

/**
 * Shared base-URL normalisation so the middleware and the check endpoint can
 * never disagree on what "the same URL" means.
 */
trait NormalizesUrl
{
    /**
     * Normalise a base URL for comparison:
     *  - trim surrounding whitespace
     *  - lower-case scheme + host
     *  - drop a trailing slash
     *  - drop the default port (:80 for http, :443 for https) so that, e.g.,
     *    http://site and http://site:80 are treated as equal (the browser host
     *    never carries the default port, but a hand-written APP_URL might).
     */
    protected function normalize(string $url): string
    {
        $url = trim($url);

        $url = preg_replace_callback(
            '#^[a-z][a-z0-9+.\-]*://[^/]*#i',
            fn ($match) => strtolower($match[0]),
            $url
        );

        $url = rtrim($url, '/');

        $url = preg_replace('#^(http://[^/:]+):80(?=$|/)#', '$1', $url);
        $url = preg_replace('#^(https://[^/:]+):443(?=$|/)#', '$1', $url);

        return $url;
    }
}
