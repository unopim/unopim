<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();

    $this->attribute = Attribute::factory()->create(['type' => 'select']);
    $this->other = Attribute::factory()->create(['type' => 'select']);
});

function apiOptionOwner(string $code): array
{
    return AttributeOption::where('code', $code)->pluck('attribute_id')->map(fn (mixed $id): int => (int) $id)->all();
}

it('keeps options created through the REST API on the attribute in the URL', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.attribute_options.store_option', $this->attribute->code), [
            ['code' => 'api_owned_new', 'attribute_id' => $this->other->id],
        ])
        ->assertCreated();

    expect(apiOptionOwner('api_owned_new'))->toBe([$this->attribute->id]);
});

it('keeps options updated or added through the REST API on the attribute in the URL', function () {
    $option = AttributeOption::create(['code' => 'api_owned_existing', 'sort_order' => 10, 'attribute_id' => $this->attribute->id]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.attribute_options.update_option', $this->attribute->code), [
            ['code' => $option->code, 'attribute_id' => $this->other->id],
            ['code' => 'api_owned_upsert', 'attribute_id' => $this->other->id],
        ])
        ->assertOk();

    expect(apiOptionOwner($option->code))->toBe([$this->attribute->id])
        ->and(apiOptionOwner('api_owned_upsert'))->toBe([$this->attribute->id]);
});

it('rejects options of another attribute in a REST attribute update', function () {
    $foreignDelete = AttributeOption::create(['code' => 'api_foreign_delete', 'sort_order' => 10, 'attribute_id' => $this->other->id]);
    $foreignUpdate = AttributeOption::create(['code' => 'api_foreign_update', 'sort_order' => 11, 'attribute_id' => $this->other->id]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.attributes.update', $this->attribute->code), [
            'options' => [
                $foreignDelete->id => ['isNew' => false, 'isDelete' => true],
                $foreignUpdate->id => ['isNew' => false, 'isDelete' => false, 'code' => 'api_hijacked', 'attribute_id' => $this->attribute->id],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.options.0', trans('validation.exists', ['attribute' => 'options']));

    expect(AttributeOption::whereKey($foreignDelete->id)->exists())->toBeTrue()
        ->and(apiOptionOwner('api_foreign_update'))->toBe([$this->other->id])
        ->and(apiOptionOwner('api_hijacked'))->toBe([]);
});

it('does not let an edit-only API key add options through the REST option update', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes.edit']);
    $option = AttributeOption::create(['code' => 'api_edit_only_existing', 'sort_order' => 1, 'attribute_id' => $this->attribute->id]);

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.attribute_options.update_option', $this->attribute->code), [
            ['code' => $option->code, 'sort_order' => 4],
            ['code' => 'api_edit_only_new'],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.api_edit_only_new.0', trans('admin::app.common.unauthorized'));

    expect(apiOptionOwner('api_edit_only_new'))->toBe([])
        ->and((int) $option->fresh()->sort_order)->toBe(4);
});

it('lets an API key holding the create permission add options through the REST option update', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes.edit', 'api.catalog.attributes.create']);

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.attribute_options.update_option', $this->attribute->code), [
            ['code' => 'api_create_allowed_new'],
        ])
        ->assertOk();

    expect(apiOptionOwner('api_create_allowed_new'))->toBe([$this->attribute->id]);
});
