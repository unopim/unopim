<?php

use Illuminate\Support\Facades\URL;

describe('Login throttle error page', function () {
    beforeEach(function () {
        config(['app.url' => 'http://localhost']);

        URL::forceRootUrl(config('app.url'));
    });

    /**
     * The custom exception handler only registers its renderables when
     * APP_DEBUG=false, which is how the lockout response is built in
     * production. Regression for: 429 lockout rendered as the
     * "500 Internal Server Error" page.
     */
    it('renders the 429 page instead of the 500 page when login attempts exceed the limit and debug is off', function () {
        config(['app.debug' => false]);

        $credentials = [
            'email'    => 'throttle-page@example.com',
            'password' => 'wrong-password',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.session.store'), $credentials)->assertRedirect();
        }

        $this->post(route('admin.session.store'), $credentials)
            ->assertSee(trans('admin::app.errors.429.title'))
            ->assertSee(trans('admin::app.errors.429.description'))
            ->assertDontSee(trans('admin::app.errors.500.title'));
    });

    it('returns a json 429 instead of a 500 for ajax login attempts past the limit when debug is off', function () {
        config(['app.debug' => false]);

        $credentials = [
            'email'    => 'throttle-json@example.com',
            'password' => 'wrong-password',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.session.store'), $credentials)->assertRedirect();
        }

        $this->postJson(route('admin.session.store'), $credentials)
            ->assertStatus(429)
            ->assertJson([
                'error'       => trans('admin::app.errors.429.title'),
                'description' => trans('admin::app.errors.429.description'),
            ]);
    });

    it('keeps the default 429 throttle response when debug is on', function () {
        $credentials = [
            'email'    => 'throttle-debug@example.com',
            'password' => 'wrong-password',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.session.store'), $credentials)->assertRedirect();
        }

        $this->post(route('admin.session.store'), $credentials)->assertStatus(429);
    });

    it('defines the 429 error translations for every locale', function () {
        $files = glob(base_path('packages/Webkul/Admin/src/Resources/lang/*/app.php'));

        expect($files)->not->toBeEmpty();

        foreach ($files as $file) {
            $lines = include $file;

            $locale = basename(dirname($file));

            $this->assertTrue(
                isset($lines['errors']['429']['title'], $lines['errors']['429']['description']),
                "Missing errors.429 translations for locale [{$locale}]."
            );
        }
    });
});
