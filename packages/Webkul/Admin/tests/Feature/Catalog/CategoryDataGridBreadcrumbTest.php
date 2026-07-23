<?php

use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;
use Webkul\Category\Models\Category;

/**
 * The categories grid used to build a breadcrumb path for EVERY category via a
 * full-tree recursive CTE on each page load, then paginate. The base query must
 * no longer materialize the whole tree — breadcrumbs are computed only for the
 * visible page after pagination.
 */
it('paginates categories without a full-tree recursive CTE in the base query', function () {
    request()->merge(['locale' => 'en_US']);

    $sql = strtolower(app(CategoryDataGrid::class)->prepareQueryBuilder()->toSql());

    expect($sql)->not->toContain('with recursive')
        ->and($sql)->not->toContain('parent_id is null');
});

it('resolves the full breadcrumb path only for the visible page', function () {
    $this->loginAsAdmin();

    $root = Category::factory()->create([
        'parent_id'       => null,
        'code'            => 'bc_root',
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => 'RootX']]],
    ]);

    $child = Category::factory()->create([
        'parent_id'       => $root->id,
        'code'            => 'bc_child',
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => 'ChildX']]],
    ]);

    $grand = Category::factory()->create([
        'parent_id'       => $child->id,
        'code'            => 'bc_grand',
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => 'GrandX']]],
    ]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.categories.index', [
            'locale'  => 'en_US',
            'filters' => ['code' => ['bc_grand']],
        ]));

    $response->assertStatus(200);

    $records = collect($response->json('records'));

    $record = $records->firstWhere('category_id', $grand->id);

    expect($record)->not->toBeNull()
        ->and($record['category_name'])->toBe('GrandX')
        ->and($record['display_name'])->toBe('RootX / ChildX / GrandX');
});
