<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Webkul\Product\Jobs\MassDeleteProducts;
use Webkul\Product\Jobs\MassUpdateProductsStatus;
use Webkul\Product\Models\Product;

use function Pest\Laravel\postJson;

uses(DatabaseTransactions::class);

it('deletes synchronously within the request when the selection is within the threshold', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.mass_delete']);

    config(['products.mass_action_async_threshold' => 200]);

    $ids = collect(range(1, 2))->map(fn (): int => Product::factory()->create()->id)->all();

    postJson(route('admin.catalog.products.mass_delete'), ['indices' => $ids])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.products.index.datagrid.mass-delete-success'));

    expect(Product::whereIn('id', $ids)->count())->toBe(0);
});

it('queues mass delete and defers execution when the selection exceeds the threshold', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.mass_delete']);

    config(['products.mass_action_async_threshold' => 1]);

    $ids = collect(range(1, 3))->map(fn (): int => Product::factory()->create()->id)->all();

    Queue::fake();

    postJson(route('admin.catalog.products.mass_delete'), ['indices' => $ids])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.products.index.datagrid.mass-delete-queued'));

    Queue::assertPushed(MassDeleteProducts::class, fn (MassDeleteProducts $job): bool => count($ids) === 3);

    expect(Product::whereIn('id', $ids)->count())->toBe(3);
});

it('updates status synchronously within the request when within the threshold', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.mass_update']);

    config(['products.mass_action_async_threshold' => 200]);

    $ids = collect(range(1, 2))->map(fn (): int => Product::factory()->create(['status' => 1])->id)->all();

    postJson(route('admin.catalog.products.mass_update'), ['indices' => $ids, 'value' => 0])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.products.index.datagrid.mass-update-success'));

    expect(Product::whereIn('id', $ids)->where('status', 0)->count())->toBe(2);
});

it('queues mass status update when the selection exceeds the threshold', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.products', 'catalog.products.mass_update']);

    config(['products.mass_action_async_threshold' => 1]);

    $ids = collect(range(1, 3))->map(fn (): int => Product::factory()->create(['status' => 1])->id)->all();

    Queue::fake();

    postJson(route('admin.catalog.products.mass_update'), ['indices' => $ids, 'value' => 0])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.products.index.datagrid.mass-update-queued'));

    Queue::assertPushed(MassUpdateProductsStatus::class);

    expect(Product::whereIn('id', $ids)->where('status', 1)->count())->toBe(3);
});
