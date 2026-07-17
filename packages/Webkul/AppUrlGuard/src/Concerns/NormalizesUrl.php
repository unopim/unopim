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
            fn ($match): string => strtolower($match[0]),
            $url
        );

        $url = rtrim((string) $url, '/');

        $url = preg_replace('#^(http://[^/:]+):80(?=$|/)#', '$1', $url);

        return preg_replace('#^(https://[^/:]+):443(?=$|/)#', '$1', (string) $url);
    }

    /**
     * Whether two base URLs refer to the same origin for the guard's purposes.
     *
     * Loopback hosts (localhost, 127.0.0.1, [::1]) are interchangeable: assets
     * pinned to one load fine from another on the same machine, so a
     * loopback-only difference is NOT the "styles won't load" bug this guard
     * exists to catch. Scheme, port and path stay significant, so
     * localhost:8000 vs 127.0.0.1:8090 (different port) is still a mismatch.
     */
    protected function matches(string $a, string $b): bool
    {
        return $this->canonicalize($this->normalize($a)) === $this->canonicalize($this->normalize($b));
    }

    /**
     * Fold loopback host aliases to a single token, leaving scheme, port and
     * path untouched.
     */
    protected function canonicalize(string $url): string
    {
        return preg_replace(
            '#^(\w[\w+.\-]*://)(?:localhost|127\.0\.0\.1|\[::1\])(?=$|[:/])#',
            '${1}localhost',
            $url
        );
    }
}
