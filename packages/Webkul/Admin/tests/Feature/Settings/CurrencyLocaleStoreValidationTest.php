<?php

use function Pest\Laravel\postJson;

/*
 * The store validation must not be bypassable by injecting an `id` into the body:
 * store/update are discriminated by HTTP verb, so a create request always gets the
 * full code rules (required/unique/length/format).
 */
it('enforces currency code rules on store even when an id is injected', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.currencies.store'), [
        'id'   => 1,
        'code' => 'XX',
    ])->assertStatus(422);
});

it('enforces locale code rules on store even when an id is injected', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.locales.store'), [
        'id'   => 1,
        'code' => '',
    ])->assertStatus(422);
});
