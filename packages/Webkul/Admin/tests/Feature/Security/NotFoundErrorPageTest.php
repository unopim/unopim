<?php

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
        'message'   => trans('admin::app.errors.404.title'),
        'errorCode' => 404,
    ]);
});
