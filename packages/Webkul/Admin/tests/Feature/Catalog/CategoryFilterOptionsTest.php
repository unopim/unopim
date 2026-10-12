<?php

use Webkul\Category\Models\Category;

function categoryOptions(array $params): array
{
    return test()->getJson(route('admin.catalog.options.fetch-all', $params + ['entityName' => 'category']))
        ->assertOk()
        ->json('options');
}

it('lists categories for the product category filter', function () {
    $this->loginAsAdmin();

    $category = Category::factory()->create();

    $codes = [];
    $page = 1;

    do {
        $response = $this->getJson(route('admin.catalog.options.fetch-all', ['entityName' => 'category', 'page' => $page]))
            ->assertOk();

        $codes = [...$codes, ...array_column($response->json('options'), 'code')];
    } while ($page++ < $response->json('lastPage'));

    expect($codes)->toContain($category->code);
});

/**
 * The multiselect re-queries this endpoint with the selected codes to rebuild its chips.
 * Ignoring `identifiers` made every category on the first page look selected.
 */
it('returns only the requested codes when hydrating selected values', function () {
    $this->loginAsAdmin();

    $selected = Category::factory()->count(2)->create();

    Category::factory()->count(3)->create();

    $options = categoryOptions([
        'identifiers' => [
            'columnName' => 'code',
            'values'     => $selected->pluck('code')->all(),
        ],
    ]);

    expect(array_column($options, 'code'))->toEqualCanonicalizing($selected->pluck('code')->all());
});

/**
 * The search matches a name as well as a code, and the factory's code is random
 * and can be two characters long, so an explicit code is what keeps the query
 * from also matching a generated name or a demo category.
 */
it('searches categories by code', function () {
    $this->loginAsAdmin();

    $category = Category::factory()->create(['code' => 'qzx_filter_target']);

    Category::factory()->count(2)->create();

    $options = categoryOptions(['query' => $category->code]);

    expect(array_column($options, 'code'))->toBe([$category->code]);
});
