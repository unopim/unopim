<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deliberately NOT Laravel's `throttle:` middleware alias: ThrottleRequests
 * throws ThrottleRequestsException, and — as established in
 * PublicationErrorBoundary's doc comment — bootstrap/app.php's own
 * unconditional ThrottleRequestsException callback (admin::errors.index,
 * admin support email) always wins the render race, so nothing this package
 * registers can intercept it after the fact. Reusing the SAME named
 * `publication` limiter (registered in PublicationServiceProvider) but
 * checking/hitting it directly and returning a Response on rejection is the
 * only way a 429 on this route group reaches our own template.
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
