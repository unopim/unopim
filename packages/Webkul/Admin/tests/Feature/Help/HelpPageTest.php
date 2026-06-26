<?php

use Illuminate\Support\Facades\Route;

it('help route is registered and named', function () {
    expect(Route::has('admin.help.index'))->toBeTrue();
});

it('help page requires authentication', function () {
    $this->get(route('admin.help.index'))->assertRedirect();
});

it('renders the help page with section labels and cards', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.help.index'));

    $response->assertStatus(200);
    $response->assertSeeText(trans('admin::app.help.index.title'));
    $response->assertSeeText(trans('admin::app.help.index.services'));
    $response->assertSeeText(trans('admin::app.help.index.resources'));
    $response->assertSeeText(trans('admin::app.help.cards.cloud-hosting.title'));
    $response->assertSeeText(trans('admin::app.help.cards.api-docs.title'));
    $response->assertSee('https://unopim.com/cloud-hosting/', false);
    $response->assertSee('https://unopim.com/contacts/', false);
    $response->assertSeeText(trans('admin::app.help.cta.button'));
});
