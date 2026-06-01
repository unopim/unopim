<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use Webkul\AppUrlGuard\Http\Middleware\VerifyAppUrlMatches;

/**
 * Feature cover for the package wiring: the debug-only check endpoint and the
 * middleware-injected modal, exercised through the real HTTP kernel.
 *
 * The test client serves from http://localhost, so APP_URL=http://localhost is
 * a "match" and any other value is a "mismatch".
 */
beforeEach(function () {
    config()->set('app.debug', true);
});

describe('check endpoint', function () {
    it('reports a mismatch as JSON', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->getJson('/app-url-guard/check')
            ->assertOk()
            ->assertJson(['matches' => false])
            ->assertJsonStructure(['matches', 'configured', 'actual']);
    });

    it('reports a match when APP_URL equals the request host', function () {
        config()->set('app.url', 'http://localhost');

        $this->getJson('/app-url-guard/check')
            ->assertOk()
            ->assertJson(['matches' => true]);
    });

    it('returns 404 when APP_DEBUG is disabled', function () {
        config()->set('app.debug', false);

        $this->getJson('/app-url-guard/check')->assertNotFound();
    });

    it('only answers GET (POST is method-not-allowed)', function () {
        $this->postJson('/app-url-guard/check')->assertStatus(405);
    });

    it('treats the http default port as a match', function () {
        config()->set('app.url', 'http://localhost:80');

        $this->getJson('/app-url-guard/check')
            ->assertOk()
            ->assertJson(['matches' => true]);
    });

    it('treats an empty APP_URL as a match (no false positive)', function () {
        config()->set('app.url', '');

        $this->getJson('/app-url-guard/check')
            ->assertOk()
            ->assertJson(['matches' => true]);
    });
});

describe('package wiring', function () {
    it('registers the guard middleware on the global stack in debug mode', function () {
        $kernel = app(Kernel::class);

        expect($kernel->hasMiddleware(VerifyAppUrlMatches::class))
            ->toBeTrue();
    });

    it('exposes the check route only in debug mode', function () {
        expect(Route::has('app_url_guard.check'))->toBeTrue();
    });
});

describe('modal injection on admin pages', function () {
    it('injects the modal on a mismatched admin login page', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('unopim-appurl-warning', false)
            ->assertSee('APP_URL Mismatch Detected', false)
            ->assertSee('app-url-guard/check', false);
    });

    it('does not inject the modal when APP_URL matches the host', function () {
        config()->set('app.url', 'http://localhost');

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('unopim-appurl-warning', false);
    });
});

describe('force-logout of an authenticated admin on mismatch', function () {
    // Logout only applies to session-backed (web group) routes such as the admin
    // pages — the stateless check endpoint has no session by design.
    it('logs out the admin and redirects to a reachable login page', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->loginAsAdmin();

        $this->get('/admin/login')
            ->assertRedirect('http://localhost/admin/login');

        $this->assertGuest('admin');
    });

    it('builds the redirect from the actual host, never the broken APP_URL', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->loginAsAdmin();

        $location = $this->get('/admin/login')->headers->get('Location');

        expect($location)
            ->toContain('http://localhost/admin/login')
            ->not->toContain('canonical.test');
    });

    it('returns a 401 JSON for an authenticated XHR request instead of redirecting', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->loginAsAdmin();

        $this->getJson('/admin/login')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);

        $this->assertGuest('admin');
    });

    it('does NOT log out the admin when APP_URL matches', function () {
        config()->set('app.url', 'http://localhost');

        $this->loginAsAdmin();

        $this->get('/admin/login');

        $this->assertAuthenticated('admin');
    });

    it('honours a custom admin URL prefix in the logout redirect', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->loginAsAdmin();

        // The session-backed route stays /admin/login (registered at boot), but
        // the redirect target is built from the runtime admin_url config.
        config()->set('app.admin_url', 'backend');

        $this->get('/admin/login')
            ->assertRedirect('http://localhost/backend/login');
    });

    it('flashes a warning message explaining the forced logout', function () {
        config()->set('app.url', 'http://canonical.test');

        $this->loginAsAdmin();

        $this->get('/admin/login')->assertSessionHas('warning');
    });

    it('does nothing when APP_DEBUG is disabled even if mismatched and logged in', function () {
        config()->set('app.url', 'http://canonical.test');
        config()->set('app.debug', false);

        $this->loginAsAdmin();

        // Debug off => guard is inert; the admin is not bounced.
        $this->get('/admin/login');

        $this->assertAuthenticated('admin');
    });
});
