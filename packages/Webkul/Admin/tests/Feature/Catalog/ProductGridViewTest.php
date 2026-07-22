<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\Product\Models\ProductGridView;
use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function viewPayload(array $overrides = []): array
{
    return array_replace([
        'filters'             => [['index' => 'status', 'value' => [1]]],
        'activeFilterIndices' => ['sku', 'status'],
        'columns'             => ['sku', 'name', 'status'],
        'sort'                => ['column' => 'sku', 'order' => 'asc'],
        'perPage'             => 20,
    ], $overrides);
}

it('saves the current grid state as a view', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.catalog.products.grid_views.store'), [
        'name'    => 'Disabled products',
        'payload' => viewPayload(),
    ])->assertOk();

    $view = ProductGridView::query()->firstWhere('name', 'Disabled products');

    expect($view)->not->toBeNull()
        ->and($view->is_shared)->toBeFalse()
        ->and($view->payload['perPage'])->toBe(20)
        ->and($view->payload['columns'])->toBe(['sku', 'name', 'status']);
});

it('overwrites a view of the same name instead of failing on the unique index', function () {
    $this->loginAsAdmin();

    $store = fn (int $perPage) => $this->postJson(route('admin.catalog.products.grid_views.store'), [
        'name'    => 'My view',
        'payload' => viewPayload(['perPage' => $perPage]),
    ])->assertOk();

    $store(10);
    $store(50);

    expect(ProductGridView::query()->where('name', 'My view')->count())->toBe(1)
        ->and(ProductGridView::query()->firstWhere('name', 'My view')->payload['perPage'])->toBe(50);
});

it('rejects a payload the grid could not apply', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.catalog.products.grid_views.store'), [
        'name'    => 'Broken',
        'payload' => viewPayload(['sort' => ['column' => 'sku', 'order' => 'sideways']]),
    ])->assertUnprocessable();

    $this->postJson(route('admin.catalog.products.grid_views.store'), [
        'name'    => 'Broken',
        'payload' => viewPayload(['perPage' => 5000]),
    ])->assertUnprocessable();
});

it('lists own views plus the ones other admins shared', function () {
    $admin = $this->loginAsAdmin();

    $other = Admin::factory()->create();

    ProductGridView::query()->create([
        'admin_id'  => $admin->id,
        'name'      => 'Mine',
        'is_shared' => false,
        'payload'   => viewPayload(),
    ]);

    ProductGridView::query()->create([
        'admin_id'  => $other->id,
        'name'      => 'Shared by a colleague',
        'is_shared' => true,
        'payload'   => viewPayload(),
    ]);

    ProductGridView::query()->create([
        'admin_id'  => $other->id,
        'name'      => 'Private to a colleague',
        'is_shared' => false,
        'payload'   => viewPayload(),
    ]);

    $views = $this->getJson(route('admin.catalog.products.grid_views.index'))
        ->assertOk()
        ->json('views');

    expect(array_column($views, 'name'))->toEqualCanonicalizing(['Mine', 'Shared by a colleague'])
        ->and(collect($views)->firstWhere('name', 'Mine')['is_owner'])->toBeTrue()
        ->and(collect($views)->firstWhere('name', 'Shared by a colleague')['is_owner'])->toBeFalse();
});

it('narrows the list with a search term', function () {
    $admin = $this->loginAsAdmin();

    foreach (['Enabled products', 'Missing images'] as $name) {
        ProductGridView::query()->create([
            'admin_id'  => $admin->id,
            'name'      => $name,
            'is_shared' => false,
            'payload'   => viewPayload(),
        ]);
    }

    $views = $this->getJson(route('admin.catalog.products.grid_views.index', ['query' => 'images']))
        ->assertOk()
        ->json('views');

    expect(array_column($views, 'name'))->toBe(['Missing images']);
});

it('lets an admin delete only their own view', function () {
    $admin = $this->loginAsAdmin();

    $other = Admin::factory()->create();

    $mine = ProductGridView::query()->create([
        'admin_id'  => $admin->id,
        'name'      => 'Mine',
        'is_shared' => false,
        'payload'   => viewPayload(),
    ]);

    $theirs = ProductGridView::query()->create([
        'admin_id'  => $other->id,
        'name'      => 'Theirs',
        'is_shared' => true,
        'payload'   => viewPayload(),
    ]);

    $this->deleteJson(route('admin.catalog.products.grid_views.delete', $theirs->id))->assertForbidden();

    $this->deleteJson(route('admin.catalog.products.grid_views.delete', $mine->id))->assertOk();

    expect(ProductGridView::query()->find($mine->id))->toBeNull()
        ->and(ProductGridView::query()->find($theirs->id))->not->toBeNull();
});
