<?php

use Webkul\Admin\DataGrids\Catalog\AttributeOptionDataGrid;
use Webkul\Attribute\Models\Attribute;

/**
 * One label column per active locale, aliased with the locale code. PostgreSQL
 * folds an unquoted alias to lower case, so `name_en_US` arrived as
 * `name_en_us` and every label cell in the grid rendered empty.
 */
beforeEach(function () {
    $this->loginAsAdmin();
});

function optionGridRecords(int $attributeId): array
{
    return test()->getJson(route('admin.catalog.attributes.options.index', $attributeId))
        ->assertOk()
        ->json('records');
}

it('hands the option label back under the column the grid asked for', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $option = $attribute->options()->first();

    $translation = $option->translations()->firstWhere('locale', 'en_US')
        ?? $option->translations()->make();

    $translation->locale = 'en_US';
    $translation->label = 'None declared';
    $option->translations()->save($translation);

    $row = collect(optionGridRecords($attribute->id))->firstWhere('code', $option->code);

    expect($row)->toHaveKey('name_en_US')
        ->and($row['name_en_US'])->toBe('None declared');
});

it('answers every locale column it declares', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $grid = app(AttributeOptionDataGrid::class)->setAttributeId($attribute->id);

    $grid->prepareColumns();

    $indexes = collect($grid->getColumns())->pluck('index')->all();

    $row = (array) $grid->prepareQueryBuilder()->first();

    expect($indexes)->not->toBeEmpty()
        ->and(array_keys($row))->toContain(...$indexes);
});
