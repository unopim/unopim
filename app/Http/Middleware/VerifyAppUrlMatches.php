<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
    /**
     * Handle an incoming request.
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! config('app.debug')) {
            return $response;
        }

        $configured = $this->normalize((string) config('app.url'));
        $actual = $this->normalize($request->getSchemeAndHttpHost().$request->getBaseUrl());

        if ($configured === '' || $configured === $actual) {
            return $response;
        }

        $this->logMismatch($configured, $actual);

        return $this->injectBanner($response, $configured, $actual);
    }

    /**
     * Normalise a base URL for comparison (lower-case host, no trailing slash).
     */
    protected function normalize(string $url): string
    {
        return rtrim(strtolower(trim($url)), '/');
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
    protected function injectBanner(Response $response, string $configured, string $actual): Response
    {
        $contentType = (string) $response->headers->get('Content-Type');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || ! str_contains($content, '</body>')) {
            return $response;
        }

        $banner = $this->bannerHtml($configured, $actual);

        $response->setContent(str_replace('</body>', $banner.'</body>', $content));

        return $response;
    }

    /**
     * Build the banner markup (values escaped to avoid any injection).
     */
    protected function bannerHtml(string $configured, string $actual): string
    {
        $appUrl = e($configured);
        $reqUrl = e($actual);

        return <<<HTML
            <div id="unopim-appurl-warning" role="alert" style="
                position:fixed;bottom:24px;right:24px;z-index:2147483647;width:380px;max-width:calc(100vw - 32px);
                font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
                background:#ffffff;border:1px solid #fed7aa;border-radius:14px;overflow:hidden;
                box-shadow:0 18px 50px -12px rgba(15,23,42,.35);animation:unopimSlideIn .35s ease-out;">

                <style>
                    @keyframes unopimSlideIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
                    #unopim-appurl-warning code{background:#f1f5f9;border-radius:5px;padding:2px 6px;
                        font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;color:#0f172a;word-break:break-all}
                    #unopim-appurl-warning .uw-btn{cursor:pointer;border:0;border-radius:8px;font-weight:600;font-size:12px}
                </style>

                <div style="display:flex;align-items:center;gap:10px;padding:13px 16px;
                            background:linear-gradient(90deg,#f59e0b,#ea580c);color:#fff;">
                    <span style="font-size:17px;line-height:1">⚠️</span>
                    <strong style="flex:1;font-size:13.5px;letter-spacing:.2px">APP_URL Mismatch Detected</strong>
                    <button class="uw-btn" onclick="document.getElementById('unopim-appurl-warning').remove()"
                            title="Dismiss" style="background:rgba(255,255,255,.22);color:#fff;width:24px;height:24px;
                            line-height:1;padding:0;font-size:15px">&times;</button>
                </div>

                <div style="padding:14px 16px;color:#334155;font-size:12.5px;line-height:1.55">
                    <p style="margin:0 0 12px">Your CSS/JS may not load — UnoPim pins all asset URLs to
                        <code>APP_URL</code>, but your browser is on a different address.</p>

                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:10px 12px;margin-bottom:12px">
                        <div style="margin-bottom:6px"><span style="color:#94a3b8">.env says&nbsp;&nbsp;</span><code>{$appUrl}</code></div>
                        <div><span style="color:#94a3b8">You're on&nbsp;</span><code style="background:#dcfce7;color:#166534">{$reqUrl}</code></div>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                        <code id="unopim-fix-value" style="flex:1">APP_URL={$reqUrl}</code>
                        <button class="uw-btn" style="background:#7c3aed;border:1px solid #6d28d9;color:#f9fafb;
                            padding:7px 12px;white-space:nowrap;transition:all .15s"
                            onmouseover="this.style.background='#8b5cf6';this.style.borderColor='#8b5cf6'"
                            onmouseout="this.style.background='#7c3aed';this.style.borderColor='#6d28d9'"
                            onclick="navigator.clipboard.writeText(document.getElementById('unopim-fix-value').innerText);
                                     this.innerText='✓ Copied';this.style.background='#16a34a';this.style.borderColor='#15803d';">Copy</button>
                    </div>

                    <ol style="margin:0;padding-left:18px;color:#475569">
                        <li>Paste the line above into <code>.env</code></li>
                        <li>Run <code>php artisan optimize:clear</code></li>
                        <li>Hard-refresh — <code>Ctrl + Shift + R</code></li>
                    </ol>
                </div>
            </div>
            HTML;
    }
}
