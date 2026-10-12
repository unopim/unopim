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

        if ($configured === '' || $this->matches($configured, $actual)) {
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

        if ($this->redirectsToUnreachableAppUrl($request, $response)) {
            return $this->warningPage($configured, $actual, $checkUrl, $justLoggedIn);
        }

        return $this->injectBanner($response, $configured, $actual, $checkUrl, $justLoggedIn);
    }

    /**
     * Detect a redirect that URL::forceRootUrl() has pinned to the (unreachable)
     * APP_URL origin. Because the browser is on a different host/port, following
     * it would bounce the developer to a host they cannot reach — and a redirect
     * carries no HTML body, so the banner could never be injected. This is the
     * "I open the real URL but land on localhost and see nothing" case.
     */
    protected function redirectsToUnreachableAppUrl(Request $request, Response $response): bool
    {
        if ($request->expectsJson() || ! $response->isRedirect()) {
            return false;
        }

        $location = (string) $response->headers->get('Location');

        if ($location === '') {
            return false;
        }

        $target = $this->originOf($location);
        $actual = $request->getSchemeAndHttpHost();
        $configured = $this->originOf((string) config('app.url'));

        return $this->normalize($configured) !== ''
            && $this->matches($target, $configured)
            && ! $this->matches($target, $actual);
    }

    /**
     * Scheme + host (+ port) of a URL, stripped of any path, query or fragment.
     */
    protected function originOf(string $url): string
    {
        $parts = parse_url($url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * Render the warning modal as a standalone HTML page (200), used in place of
     * a redirect the browser could not have followed back to a visible page.
     */
    protected function warningPage(string $configured, string $actual, string $checkUrl, bool $justLoggedIn): Response
    {
        $banner = $this->renderBanner($configured, $actual, $checkUrl, $justLoggedIn);

        $title = e(trans('app_url_guard::app.warning.title'));

        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.$title.'</title></head><body>'.$banner.'</body></html>';

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
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
        logger()->warning(trans('app_url_guard::app.log.mismatch'), [
            'app_url' => $configured,
            'request' => $actual,
            'hint'    => trans('app_url_guard::app.log.hint'),
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

        $banner = $this->renderBanner($configured, $actual, $checkUrl, $justLoggedIn);

        $response->setContent(str_replace('</body>', $banner.'</body>', $content));

        return $response;
    }

    /**
     * Render the warning modal markup (the backdrop, styles and script).
     */
    protected function renderBanner(string $configured, string $actual, string $checkUrl, bool $justLoggedIn): string
    {
        return view()->file(__DIR__.'/../../Resources/views/warning.blade.php', [
            'configured'   => $configured,
            'actual'       => $actual,
            'checkUrl'     => $checkUrl,
            'justLoggedIn' => $justLoggedIn,
        ])->render();
    }
}
