<?php

use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * The handler only installs its renderables when debug is off, and the suite runs with
 * APP_DEBUG=true, so the bound instance has to be rebuilt with debug disabled.
 */
beforeEach(function () {
    config(['app.debug' => false]);

    app()->forgetInstance(ExceptionHandler::class);
});

it('renders the styled UnoPim 404 page for an unknown web route', function () {
    $response = $this->get('admin/this-route-does-not-exist-'.uniqid());

    $response->assertNotFound();
    $response->assertViewIs('admin::errors.index');
    $response->assertViewHas('errorCode', 404);
    $response->assertSeeText(trans('admin::app.errors.404.title'));
});

it('returns a json 404 body for an unknown route when json is requested', function () {
    $response = $this->getJson('admin/this-route-does-not-exist-'.uniqid());

    $response->assertNotFound();
    $response->assertJson([
        'error'       => trans('admin::app.errors.404.title'),
        'description' => trans('admin::app.errors.404.description'),
    ]);
});
