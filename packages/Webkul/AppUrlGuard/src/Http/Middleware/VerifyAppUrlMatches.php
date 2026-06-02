<?php

namespace Webkul\AppUrlGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AppUrlGuard\Concerns\NormalizesUrl;
use Webkul\AppUrlGuard\Providers\AppUrlGuardServiceProvider;

/**
 * Developer guard: warns when the URL the browser is actually using does
 * not match APP_URL in .env.
 *
 * Why this matters: CoreServiceProvider calls URL::forceRootUrl(APP_URL),
 * so every generated url()/asset()/Vite link is pinned to APP_URL. If the
 * browser is on a different host/port/sub-path, the CSS & JS silently 404
 * (the classic "styles not loading" bug). This middleware surfaces that
 * mismatch instead of leaving it silent.
 *
 * Active ONLY when APP_DEBUG=true, so it has zero effect in production.
 */
class VerifyAppUrlMatches
{
    use NormalizesUrl;

    /**
     * Handle an incoming request.
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! config('app.debug') || ! AppUrlGuardServiceProvider::active()) {
            return $response;
        }

        $configured = $this->normalize((string) config('app.url'));
        $actual = $this->normalize($request->getSchemeAndHttpHost().$request->getBaseUrl());

        if ($configured === '' || $configured === $actual) {
            return $response;
        }

        if (! $request->hasSession() || ! session()->has('unopim_appurl_logged')) {
            $this->logMismatch($configured, $actual);

            if ($request->hasSession()) {
                session(['unopim_appurl_logged' => true]);
            }
        }

        if ($request->hasSession() && auth()->guard('admin')->check()) {
            return $this->logoutMismatchedAdmin($request);
        }

        $justLoggedIn = false;
        if ($request->hasSession()) {
            if (auth()->guard('admin')->check()) {
                if (! session()->has('unopim_appurl_checked_auth')) {
                    session(['unopim_appurl_checked_auth' => true]);
                    $justLoggedIn = true;
                }
            } else {
                session()->forget('unopim_appurl_checked_auth');
            }
        }

        $checkUrl = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/').'/app-url-guard/check';

        return $this->injectBanner($response, $configured, $actual, $checkUrl, $justLoggedIn);
    }

    /**
     * Sign the admin out and send them to a reachable login page (or a JSON 401
     * for XHR/API callers). The redirect is built from the actual request host
     * so it never points at the unreachable, misconfigured APP_URL.
     */
    protected function logoutMismatchedAdmin(Request $request): Response
    {
        auth()->guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = trans('app_url_guard::app.warning.logged-out');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 401);
        }

        $base = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
        $loginUrl = $base.'/'.trim((string) config('app.admin_url'), '/').'/login';

        return redirect($loginUrl)->with('warning', $message);
    }

    /**
     * Emit a log line so the mismatch is visible even for API/JSON requests.
     */
    protected function logMismatch(string $configured, string $actual): void
    {
        logger()->warning('APP_URL mismatch detected', [
            'app_url' => $configured,
            'request' => $actual,
            'hint'    => 'Update APP_URL in .env to the request URL, then run: php artisan optimize:clear',
        ]);
    }

    /**
     * Inject a warning banner into HTML responses only.
     */
    protected function injectBanner(Response $response, string $configured, string $actual, string $checkUrl, bool $justLoggedIn): Response
    {
        $contentType = (string) $response->headers->get('Content-Type');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || ! str_contains($content, '</body>')) {
            return $response;
        }

        $banner = view()->file(__DIR__.'/../../Resources/views/warning.blade.php', [
            'configured'   => $configured,
            'actual'       => $actual,
            'checkUrl'     => $checkUrl,
            'justLoggedIn' => $justLoggedIn,
        ])->render();

        $response->setContent(str_replace('</body>', $banner.'</body>', $content));

        return $response;
    }
}
