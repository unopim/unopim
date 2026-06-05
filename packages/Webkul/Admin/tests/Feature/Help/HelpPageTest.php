<?php

use Illuminate\Support\Facades\Route;

it('help route is registered and named', function () {
    expect(Route::has('admin.help.index'))->toBeTrue();
});

it('help page requires authentication', function () {
    $this->get(route('admin.help.index'))->assertRedirect();
});
