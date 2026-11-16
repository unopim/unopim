<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('renders the enable templates switch on the attribute create page', function () {
    get(route('admin.catalog.attributes.create'))
        ->assertOk()
        ->assertSee('name="enable_templates"', false)
        ->assertSee('v-show="templatableType"', false)
        ->assertSee(trans('admin::app.catalog.attributes.create.enable-templates'));
});

it('renders the enable templates switch on the edit page of text and textarea attributes', function (string $type) {
    $attribute = Attribute::factory()->create(['type' => $type, 'validation' => null, 'is_unique' => false]);

    get(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertOk()
        ->assertSee('name="enable_templates"', false)
        ->assertSee(':disabled="! templatesSupported"', false)
        ->assertSee(trans('admin::app.catalog.attributes.create.enable-templates'));
})->with(['text', 'textarea']);

it('does not render the enable templates switch on the edit page of other attribute types', function (string $type) {
    $attribute = Attribute::factory()->create(['type' => $type]);

    get(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertOk()
        ->assertDontSee('name="enable_templates"', false);
})->with(['price', 'boolean', 'date', 'image']);

it('stores the enable templates flag from the attribute create form', function (string $type) {
    postJson(route('admin.catalog.attributes.store'), [
        'code'             => 'tpl_create_'.$type,
        'type'             => $type,
        'enable_templates' => 1,
    ])->assertStatus(302);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'code'             => 'tpl_create_'.$type,
        'enable_templates' => 1,
    ]);
})->with(['text', 'textarea']);

it('stores the flag as disabled when the create form sends the hidden default', function () {
    postJson(route('admin.catalog.attributes.store'), [
        'code'             => 'tpl_create_hidden_default',
        'type'             => 'text',
        'enable_templates' => 0,
    ])->assertStatus(302);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'code'             => 'tpl_create_hidden_default',
        'enable_templates' => 0,
    ]);
});

it('rejects the create form when templates are enabled on an unsupported attribute', function (array $payload) {
    postJson(route('admin.catalog.attributes.store'), [
        'code'             => 'tpl_create_invalid',
        'enable_templates' => 1,
        ...$payload,
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.enable_templates.0', trans('admin::app.catalog.attributes.create.templates-unsupported'));

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['code' => 'tpl_create_invalid']);
})->with([
    'validation' => [['type' => 'text', 'validation' => 'email']],
    'unique'     => [['type' => 'text', 'is_unique' => 1]],
    'price'      => [['type' => 'price']],
]);

it('toggles the enable templates flag from the attribute edit form', function () {
    $attribute = Attribute::factory()->create(['type' => 'textarea', 'validation' => null, 'is_unique' => false]);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'enable_templates' => 1,
    ])->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    expect((int) $attribute->fresh()->enable_templates)->toBe(1);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'enable_templates' => 0,
    ])->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    expect((int) $attribute->fresh()->enable_templates)->toBe(0);
});

it('rejects the edit form when templates are enabled on a unique attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => null, 'is_unique' => true]);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'enable_templates' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.enable_templates.0', trans('admin::app.catalog.attributes.create.templates-unsupported'));

    expect((int) $attribute->fresh()->enable_templates)->toBe(0);
});

it('allows adding input validation when the edit form turns templates off', function () {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => null, 'is_unique' => false, 'enable_templates' => 1]);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'validation'       => 'email',
        'enable_templates' => 0,
    ])->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $attribute->refresh();

    expect($attribute->validation)->toBe('email')
        ->and((int) $attribute->enable_templates)->toBe(0);
});
