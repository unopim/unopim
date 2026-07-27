<?php

use Webkul\Category\Models\CategoryField;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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

/*
 * Input validations only apply to free-text input, so they can neither be
 * demanded from, nor accepted for, any other field type.
 */
it('creates a non-text category field without an input validation', function (string $type) {
    $this->loginWithPermissions('all', ['dashboard']);

    $code = 'nv_field_'.uniqid();

    postJson(route('admin.catalog.category_fields.store'), [
        'code'    => $code,
        'type'    => $type,
        'section' => 'left',
    ])->assertStatus(302);

    expect(CategoryField::where('code', $code)->exists())->toBeTrue();
})->with(['textarea', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'file', 'checkbox']);

it('rejects an input validation on a non-text category field', function (string $type) {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code'       => 'iv_field_'.uniqid(),
        'type'       => $type,
        'section'    => 'left',
        'validation' => 'email',
    ])->assertStatus(422)->assertJsonValidationErrors('validation');
})->with(['textarea', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'file', 'checkbox']);

it('tolerates an empty input validation on a non-text category field', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    // The API and factories send an empty string rather than omitting the key.
    postJson(route('admin.catalog.category_fields.store'), [
        'code'       => 'ev_field_'.uniqid(),
        'type'       => 'boolean',
        'section'    => 'left',
        'validation' => '',
    ])->assertStatus(302);
});

it('still accepts an input validation on a text category field', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $code = 'tv_field_'.uniqid();

    postJson(route('admin.catalog.category_fields.store'), [
        'code'       => $code,
        'type'       => 'text',
        'section'    => 'left',
        'validation' => 'email',
    ])->assertStatus(302);

    expect(CategoryField::where('code', $code)->value('validation'))->toBe('email');
});

it('does not require an input validation on a text category field', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code'    => 'nt_field_'.uniqid(),
        'type'    => 'text',
        'section' => 'left',
    ])->assertStatus(302);
});

it('requires a regex pattern when the validation is regex', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code'       => 'rx_field_'.uniqid(),
        'type'       => 'text',
        'section'    => 'left',
        'validation' => 'regex',
    ])->assertStatus(422)->assertJsonValidationErrors('regex_pattern');
});

it('rejects a regex pattern when the validation is not regex', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    postJson(route('admin.catalog.category_fields.store'), [
        'code'          => 'rp_field_'.uniqid(),
        'type'          => 'textarea',
        'section'       => 'left',
        'regex_pattern' => '/^a$/',
    ])->assertStatus(422)->assertJsonValidationErrors('regex_pattern');
});

it('persists is_unique and value_per_locale on update', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $categoryField = CategoryField::factory()->create([
        'type'             => 'text',
        'is_unique'        => 0,
        'value_per_locale' => 0,
    ]);

    putJson(route('admin.catalog.category_fields.update', $categoryField->id), [
        'code'             => $categoryField->code,
        'type'             => 'text',
        'section'          => 'left',
        'position'         => 0,
        'is_unique'        => 1,
        'value_per_locale' => 1,
    ])->assertStatus(302);

    $categoryField->refresh();

    expect($categoryField->is_unique)->toEqual(1)
        ->and($categoryField->value_per_locale)->toEqual(1);
});

it('rejects an input validation on update for a non-text category field', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $categoryField = CategoryField::factory()->create(['type' => 'boolean']);

    putJson(route('admin.catalog.category_fields.update', $categoryField->id), [
        'code'       => $categoryField->code,
        'type'       => 'boolean',
        'section'    => 'left',
        'validation' => 'url',
    ])->assertStatus(422)->assertJsonValidationErrors('validation');
});
