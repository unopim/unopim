<?php

it('declares a BCP-47 language tag on the sign-in page', function () {
    $this->get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('lang="en-US"', false)
        ->assertSee('<meta http-equiv="content-language" content="en-US">', false)
        ->assertDontSee('lang="en_US"', false);
});

it('declares a BCP-47 language tag on authenticated admin pages', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('lang="en-US"', false)
        ->assertSee('<meta http-equiv="content-language" content="en-US">', false)
        ->assertDontSee('lang="en_US"', false);
});

it('declares a BCP-47 language tag on pages with history', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.attributes.edit', 1))
        ->assertOk()
        ->assertSee('lang="en-US"', false)
        ->assertDontSee('lang="en_US"', false);
});
