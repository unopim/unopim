<?php

it('stores a record via the repository and returns a redirect url', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('admin.resource-kit-items.store'), [
        'name' => 'Acme',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['redirect_url'], 'message']);

    $this->assertDatabaseHas('wk_resource_kit_items', ['name' => 'Acme']);
});

it('rejects invalid input via the FormRequest', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.resource-kit-items.store'), ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('persists schema fields that have no validation rules', function () {
    $this->loginAsAdmin();

    // `label` has no ->rules(), so it's absent from validated() but must still persist.
    $response = $this->postJson(route('admin.resource-kit-items.store'), [
        'name'  => 'Acme',
        'label' => 'Blue',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['redirect_url'], 'message']);

    $this->assertDatabaseHas('wk_resource_kit_items', [
        'name'  => 'Acme',
        'label' => 'Blue',
    ]);
});
