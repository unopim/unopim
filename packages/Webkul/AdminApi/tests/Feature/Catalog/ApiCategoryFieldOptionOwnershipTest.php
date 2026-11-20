<?php

use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();

    $this->field = CategoryField::factory()->create(['type' => 'select']);
    $this->other = CategoryField::factory()->create(['type' => 'select']);
});

function apiCategoryFieldOptionOwner(string $code): array
{
    return CategoryFieldOption::where('code', $code)->pluck('category_field_id')->map(fn (mixed $id): int => (int) $id)->all();
}

it('keeps options created through the REST API on the category field in the URL', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.category-fields-options.store_option', $this->field->code), [
            ['code' => 'api_cf_owned_new', 'category_field_id' => $this->other->id],
        ])
        ->assertCreated();

    expect(apiCategoryFieldOptionOwner('api_cf_owned_new'))->toBe([$this->field->id]);
});

it('keeps options updated through the REST API on the category field in the URL', function () {
    $option = CategoryFieldOption::factory()->create(['code' => 'api_cf_owned_existing', 'category_field_id' => $this->field->id]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields-options.update_option', $this->field->code), [
            ['code' => $option->code, 'sort_order' => 7, 'category_field_id' => $this->other->id],
        ])
        ->assertOk();

    expect(apiCategoryFieldOptionOwner($option->code))->toBe([$this->field->id])
        ->and((int) $option->fresh()->sort_order)->toBe(7);
});

it('does not let an edit-only API key add or delete options through the REST category field update', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields.edit']);
    $option = CategoryFieldOption::factory()->create(['code' => 'api_cf_edit_only_kept', 'category_field_id' => $this->field->id]);

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.category-fields.update', $this->field->code), [
            'options' => ['option_0' => ['code' => 'api_cf_edit_only_added', 'isNew' => true, 'isDelete' => false]],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.options.0', trans('admin::app.common.unauthorized'));

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.category-fields.update', $this->field->code), [
            'options' => [$option->id => ['isNew' => false, 'isDelete' => true]],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.options.0', trans('admin::app.common.unauthorized'));

    expect(apiCategoryFieldOptionOwner('api_cf_edit_only_added'))->toBe([])
        ->and(CategoryFieldOption::whereKey($option->id)->exists())->toBeTrue();
});

it('does not let a labels wrapper smuggle options past the edit-only gate on the REST category field update', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields.edit']);

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.category-fields.update', $this->field->code), [
            'labels' => ['options' => ['code' => 'api_cf_labels_smuggled', 'isNew' => true, 'isDelete' => false]],
        ]);

    expect(apiCategoryFieldOptionOwner('api_cf_labels_smuggled'))->toBe([]);
});

it('validates options carried by the REST category field update like the option routes', function () {
    $existing = CategoryFieldOption::factory()->create(['code' => 'api_cf_valid_existing', 'category_field_id' => $this->field->id]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields.update', $this->field->code), [
            'options' => ['option_0' => ['code' => 'bad code!', 'isNew' => true, 'isDelete' => false]],
        ])
        ->assertUnprocessable();

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields.update', $this->field->code), [
            'options' => [$existing->id => ['code' => 'bad code!', 'isNew' => false, 'isDelete' => false]],
        ])
        ->assertUnprocessable();

    expect(apiCategoryFieldOptionOwner('bad code!'))->toBe([]);
});
