<?php

it('does not offer internal product types on the create page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.products.index'))
        ->assertOk()
        ->assertDontSee('product::app.type.variant-group')
        ->assertDontSee(e(trans('product::app.type.variant-group')));
});
