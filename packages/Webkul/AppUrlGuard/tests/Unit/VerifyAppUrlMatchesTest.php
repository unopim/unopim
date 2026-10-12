<?php

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AppUrlGuard\Http\Middleware\VerifyAppUrlMatches;

/**
 * A $next closure that returns a canned response, mimicking the rest of the
 * pipeline.
 */
function guardPass(string $html = '<html><body>page</body></html>', string $type = 'text/html'): Closure
{
    return fn ($request) => new Response($html, 200, ['Content-Type' => $type]);
}

function guardHandle(Request $request, ?Closure $next = null): Response
{
    return (new VerifyAppUrlMatches)->handle($request, $next ?? guardPass());
}

/**
 * Render the warning component in isolation with overridable props.
 */
function renderWarning(
    string $configured = 'http://localhost:8000',
    string $actual = 'http://canonical.test',
    string $checkUrl = 'http://canonical.test/app-url-guard/check',
    bool $justLoggedIn = false,
): string {
    return view()->file(
        base_path('packages/Webkul/AppUrlGuard/src/Resources/views/warning.blade.php'),
        compact('configured', 'actual', 'checkUrl', 'justLoggedIn'),
    )->render();
}

/**
 * Build a request as Apache would when the app lives in a sub-directory
 * (public/ is NOT the document root), e.g. /erp/public/index.php.
 */
function apacheSubdirRequest(string $url): Request
{
    return Request::create($url, 'GET', [], [], [], [
        'SCRIPT_NAME'     => '/erp/public/index.php',
        'SCRIPT_FILENAME' => '/var/www/erp/public/index.php',
        'PHP_SELF'        => '/erp/public/index.php',
    ]);
}

beforeEach(function () {
    config()->set('app.debug', true);
    config()->set('app_url_guard.enabled', true);
});

describe('mismatch detection', function () {
    it('injects the warning when the browser host differs from APP_URL', function () {
        config()->set('app.url', 'http://canonical.test');

        expect(guardHandle(Request::create('http://other.test/admin/login'))->getContent())
            ->toContain('unopim-appurl-warning')
            ->toContain('APP_URL Mismatch Detected');
    });

    it('does nothing when the host already matches APP_URL', function () {
        config()->set('app.url', 'http://canonical.test');

        expect(guardHandle(Request::create('http://canonical.test/admin/login'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });

    it('is fully disabled when APP_DEBUG is false', function () {
        config()->set('app.debug', false);
        config()->set('app.url', 'http://canonical.test');

        expect(guardHandle(Request::create('http://other.test/admin/login'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });

    it('skips when APP_URL is empty so there are no false positives', function () {
        config()->set('app.url', '');

        expect(guardHandle(Request::create('http://other.test/admin/login'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });
});

describe('normalisation edge cases', function () {
    it('ignores a trailing slash on APP_URL', function () {
        config()->set('app.url', 'http://canonical.test/');

        expect(guardHandle(Request::create('http://canonical.test/admin'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });

    it('ignores case differences in scheme and host', function () {
        config()->set('app.url', 'HTTP://Canonical.TEST');

        expect(guardHandle(Request::create('http://canonical.test/admin'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });

    it('treats a different port as a mismatch', function () {
        config()->set('app.url', 'http://canonical.test:8000');

        expect(guardHandle(Request::create('http://canonical.test:8090/admin'))->getContent())
            ->toContain('unopim-appurl-warning');
    });

    it('treats a different scheme (http vs https) as a mismatch', function () {
        config()->set('app.url', 'https://canonical.test');

        expect(guardHandle(Request::create('http://canonical.test/admin'))->getContent())
            ->toContain('unopim-appurl-warning');
    });
});

describe('response-type guards', function () {
    it('does not touch non-HTML (JSON) responses', function () {
        config()->set('app.url', 'http://canonical.test');

        $response = guardHandle(Request::create('http://other.test/api'), guardPass('{"ok":true}', 'application/json'));

        expect($response->getContent())->toBe('{"ok":true}');
    });

    it('does not touch HTML without a closing body tag', function () {
        config()->set('app.url', 'http://canonical.test');

        $response = guardHandle(Request::create('http://other.test/x'), guardPass('<div>fragment</div>', 'text/html'));

        expect($response->getContent())->toBe('<div>fragment</div>');
    });
});

describe('Apache vs Nginx base path handling', function () {
    it('matches on Nginx where public/ is the document root (no sub-path)', function () {
        config()->set('app.url', 'http://shop.test');

        $request = Request::create('http://shop.test/admin/login');

        expect($request->getBaseUrl())->toBe('');
        expect(guardHandle($request)->getContent())->not->toContain('unopim-appurl-warning');
    });

    it('matches on Apache where the app lives in a sub-directory', function () {
        config()->set('app.url', 'http://shop.test/erp/public');

        $request = apacheSubdirRequest('http://shop.test/erp/public/admin/login');

        expect($request->getBaseUrl())->toBe('/erp/public');
        expect(guardHandle($request)->getContent())->not->toContain('unopim-appurl-warning');
    });

    it('flags a mismatch when the Apache sub-path is missing from APP_URL', function () {
        config()->set('app.url', 'http://shop.test');

        $request = apacheSubdirRequest('http://shop.test/erp/public/admin/login');

        expect(guardHandle($request)->getContent())->toContain('unopim-appurl-warning');
    });
});

describe('security', function () {
    it('ignores a spoofed X-Forwarded-Host from an untrusted proxy', function () {
        config()->set('app.url', 'http://canonical.test');

        $request = Request::create('http://canonical.test/admin', 'GET', [], [], [], [
            'REMOTE_ADDR'            => '198.51.100.10',
            'HTTP_X_FORWARDED_HOST'  => 'evil.example.com',
            'HTTP_X_FORWARDED_PROTO' => 'http',
        ]);

        expect(guardHandle($request)->getContent())->not->toContain('unopim-appurl-warning');
    });

    it('ignores a spoofed X-Forwarded-Proto from an untrusted proxy', function () {
        config()->set('app.url', 'http://canonical.test');

        $request = Request::create('http://canonical.test/admin', 'GET', [], [], [], [
            'REMOTE_ADDR'            => '198.51.100.10',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        expect(guardHandle($request)->getContent())->not->toContain('unopim-appurl-warning');
    });

    it('escapes injected values so the banner cannot become an XSS sink', function () {
        $html = renderWarning(configured: 'http://x"><script>alert(1)</script>');

        expect($html)
            ->not->toContain('<script>alert(1)</script>')
            ->toContain('&lt;script&gt;');
    });

    it('escapes a value that tries to break out of an HTML attribute', function () {
        $html = renderWarning(actual: 'http://x" onmouseover="alert(1)');

        expect($html)
            ->not->toContain('" onmouseover="alert(1)')
            ->toContain('&quot;');
    });
});

describe('normalisation parity with the comparison', function () {
    it('does not warn when APP_URL carries the http default port', function () {
        config()->set('app.url', 'http://canonical.test:80');

        expect(guardHandle(Request::create('http://canonical.test/admin'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });

    it('does not warn when APP_URL has surrounding whitespace', function () {
        config()->set('app.url', '   http://canonical.test   ');

        expect(guardHandle(Request::create('http://canonical.test/admin'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });

    it('skips when APP_URL config is null', function () {
        config()->set('app.url', null);

        expect(guardHandle(Request::create('http://other.test/admin'))->getContent())
            ->not->toContain('unopim-appurl-warning');
    });
});

describe('response integrity', function () {
    it('injects into HTML responses that declare a charset', function () {
        config()->set('app.url', 'http://canonical.test');

        $next = guardPass('<html><body>x</body></html>', 'text/html; charset=UTF-8');

        expect(guardHandle(Request::create('http://other.test/x'), $next)->getContent())
            ->toContain('unopim-appurl-warning');
    });

    it('preserves the status code and Location header of a redirect', function () {
        config()->set('app.url', 'http://canonical.test');

        $next = fn ($request) => redirect('http://other.test/next');
        $response = guardHandle(Request::create('http://other.test/x'), $next);

        expect($response->getStatusCode())->toBe(302);
        expect($response->headers->get('Location'))->toBe('http://other.test/next');
    });

    it('renders the check URL and matching markup into the modal', function () {
        config()->set('app.url', 'http://canonical.test');

        $content = guardHandle(Request::create('http://other.test/admin/login'))->getContent();

        expect($content)
            ->toContain('unopim-appurl-warning')
            ->toContain('data-check-url="http://other.test/app-url-guard/check"')
            ->toContain('APP_URL Mismatch Detected');
    });

    it('also escapes the just-logged-in flag and renders the configured value', function () {
        $html = renderWarning(configured: 'http://localhost:8000', justLoggedIn: true);

        expect($html)
            ->toContain('data-just-logged-in="true"')
            ->toContain('http://localhost:8000');
    });
});
