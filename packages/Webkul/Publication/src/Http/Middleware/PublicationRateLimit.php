<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deliberately not Laravel's `throttle:` alias: ThrottleRequestsException
 * would be rendered by the global handler before this package can intercept
 * it (see PublicationErrorBoundary). Reuses the same `publication` limiter
 * but checks/hits it directly and returns a Response on rejection.
 */
class PublicationRateLimit
{
    private const LIMITER_NAME = 'publication';

    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $definition = $this->limiter->limiter(self::LIMITER_NAME);

        if ($definition === null) {
            throw new RuntimeException('The ['.self::LIMITER_NAME.'] rate limiter is not registered.');
        }

        $limits = Collection::wrap($definition($request));

        foreach ($limits as $limit) {
            $key = $this->key($limit);

            if ($this->limiter->tooManyAttempts($key, $limit->maxAttempts)) {
                return response()->view('publication::errors.429', [], 429, [
                    'Retry-After' => (string) $this->limiter->availableIn($key),
                ]);
            }
        }

        foreach ($limits as $limit) {
            $this->limiter->hit($this->key($limit), $limit->decaySeconds);
        }

        return $next($request);
    }

    private function key(object $limit): string
    {
        return md5(self::LIMITER_NAME.$limit->key);
    }
}
