<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;

uses(DatabaseTransactions::class);

it('generates option codes that stay unique per field under a case-insensitive collation', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    CategoryFieldOption::factory()
        ->count(300)
        ->create(['category_field_id' => $field->id]);

    $codes = $field->options()->pluck('code')->map(fn (string $code) => strtolower($code));

    expect($codes->count())->toBeGreaterThanOrEqual(300)
        ->and($codes->unique())->toHaveCount($codes->count());
});
