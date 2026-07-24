<?php

use function Pest\Laravel\postJson;

/*
 * The admin category-field form must constrain type/section to their allowed
 * sets — matching the API path — so an unknown field type cannot be persisted.
 */
it('rejects an unknown category field type', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code' => 'perf_field_'.uniqid(),
        'type' => 'not_a_real_type',
    ])->assertStatus(422);
});

it('rejects an invalid section', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code'    => 'perf_field_'.uniqid(),
        'type'    => 'text',
        'section' => 'middle',
    ])->assertStatus(422);
});

it('accepts a valid category field', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code'       => 'perf_field_'.uniqid(),
        'type'       => 'text',
        'section'    => 'left',
        'validation' => 'none',
    ])->assertStatus(302);
});
