<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Jobs\ExportDataGridSelection;
use Webkul\Notification\Models\Notification;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

class SmallChunkExportDataGridSelection extends ExportDataGridSelection
{
    const CHUNK_SIZE = 2;
}

class RowLimitedExportDataGridSelection extends ExportDataGridSelection
{
    protected function rowLimit(): ?int
    {
        return 1;
    }
}

beforeEach(function () {
    Storage::fake('private');

    $this->loginAsAdmin();
});

function createQuickExportProducts(string $prefix, int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $index) => Product::factory()->simple()->create(['sku' => $prefix.$index])->id)
        ->all();
}

function latestQuickExportFile(): ?string
{
    return collect(Storage::disk('private')->allFiles(ExportDataGridSelection::DIRECTORY))->last();
}

it('queues the export of every product matching the filters instead of downloading it', function () {
    Bus::fake();

    $this->postJson(route('admin.catalog.products.quick-export.queue'), [
        'select_all' => true,
        'format'     => 'csv',
        'filters'    => ['sku' => ['QEXPQ-']],
    ])->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.products.index.datagrid.select-all.export.queued'));

    Bus::assertDispatched(ExportDataGridSelection::class);
});

it('validates the format of a queued export', function () {
    $this->postJson(route('admin.catalog.products.quick-export.queue'), [
        'select_all' => true,
        'format'     => 'pdf',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('format');
});

it('writes every matching product to the export file in chunks, leaving the rest out', function () {
    createQuickExportProducts('QEXPM-', 5);
    createQuickExportProducts('QEXPO-', 2);

    $adminId = auth()->guard('admin')->id();

    (new SmallChunkExportDataGridSelection(
        ProductDataGrid::class,
        ['filters' => ['sku' => ['QEXPM-']]],
        'csv',
        $adminId,
        'admin::app.catalog.products.index.datagrid.select-all.export',
        'admin.catalog.products.quick-export.download',
    ))->handle();

    $file = latestQuickExportFile();

    expect($file)->toStartWith(ExportDataGridSelection::DIRECTORY.'/'.$adminId.'/')
        ->and($file)->toEndWith('.csv');

    $contents = Storage::disk('private')->get($file);

    foreach (range(1, 5) as $index) {
        expect($contents)->toContain('QEXPM-'.$index);
    }

    expect($contents)->not->toContain('QEXPO-');

    $notification = Notification::query()->latest('id')->first();

    expect($notification->route)->toBe('admin.catalog.products.quick-export.download')
        ->and($notification->route_params)->toBe(['file' => basename($file)])
        ->and($notification->userNotifications()->pluck('admin_id')->all())->toBe([$adminId]);
});

it('writes a spreadsheet when the queued export asks for xlsx', function () {
    createQuickExportProducts('QEXPX-', 3);

    (new SmallChunkExportDataGridSelection(
        ProductDataGrid::class,
        ['filters' => ['sku' => ['QEXPX-']]],
        'xlsx',
        auth()->guard('admin')->id(),
        'admin::app.catalog.products.index.datagrid.select-all.export',
        'admin.catalog.products.quick-export.download',
    ))->handle();

    $file = latestQuickExportFile();

    expect($file)->toEndWith('.xlsx')
        ->and(Storage::disk('private')->get($file))->toStartWith('PK');
});

it('fails the export with a notification when the format cannot hold every row', function () {
    createQuickExportProducts('QEXPL-', 2);

    (new RowLimitedExportDataGridSelection(
        ProductDataGrid::class,
        ['filters' => ['sku' => ['QEXPL-']]],
        'xlsx',
        auth()->guard('admin')->id(),
        'admin::app.catalog.products.index.datagrid.select-all.export',
        'admin.catalog.products.quick-export.download',
    ))->handle();

    expect(latestQuickExportFile())->toBeNull();

    $notification = Notification::query()->latest('id')->first();

    expect($notification->route)->toBeNull()
        ->and($notification->description)->toBe(trans('admin::app.catalog.products.index.datagrid.select-all.export.too-many-rows', ['limit' => 1]));
});

it('downloads a finished export only for the admin who started it', function () {
    $adminId = auth()->guard('admin')->id();

    $file = '0b0f7d52-6f3e-4a55-9a53-2b5d4f8a1c11.csv';

    Storage::disk('private')->put(ExportDataGridSelection::DIRECTORY.'/'.$adminId.'/'.$file, "sku\nQEXPD-1\n");

    $this->get(route('admin.catalog.products.quick-export.download', ['file' => $file]))
        ->assertOk()
        ->assertDownload();

    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->get(route('admin.catalog.products.quick-export.download', ['file' => $file]))
        ->assertNotFound();
});

it('rejects a download path that is not an export file name', function () {
    $this->get(route('admin.catalog.products.quick-export.download', ['file' => '..%2F.env']))
        ->assertNotFound();
});

it('accepts the quick export id list in a POST body, so a long selection is not cut off by the URL length', function () {
    $ids = createQuickExportProducts('QEXPP-', 2);

    $this->post(route('admin.catalog.products.quick-export'), [
        'export'     => 1,
        'format'     => 'csv',
        'productIds' => $ids,
    ])->assertOk()
        ->assertDownload();
});

it('tells the grid how many ids a mass action without select-all support can resolve', function () {
    $this->getJson(route('admin.catalog.products.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('meta.mass_action_id_limit', ProductDataGrid::MASS_ACTION_ID_LIMIT);
});
