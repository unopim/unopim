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
