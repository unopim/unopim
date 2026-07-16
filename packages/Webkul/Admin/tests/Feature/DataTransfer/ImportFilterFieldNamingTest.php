<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;

use function Pest\Laravel\postJson;

/*
 * Regression guard for the import filter field-naming fix.
 *
 * The filter inputs (text/date/datetime/textarea) previously bound the bare
 * `:name="filterField.name"`, so a value for a field called e.g. "sku" was
 * submitted as `sku=...` instead of `filters[sku]=...`. The server reads the
 * filters via `request()->only(['filters', ...])`, so nothing landed under
 * `filters`. The fix brackets every filter input as
 * `:name="'filters[' + filterField.name + ']'"`.
 *
 * These tests assert BOTH sides of the contract:
 *   1. The server actually persists a bracketed `filters[...]` payload as an array.
 *   2. The Blade source keeps emitting the bracketed name for the four field
 *      types that were fixed (and no longer emits the buggy bare binding).
 */

it('persists a bracketed filters[...] payload as an array on the job instance', function () {
    $this->loginAsAdmin();

    Storage::fake();

    $code = fake()->unique()->word;

    $payload = [
        'code'                => $code,
        'entity_type'         => 'products',
        'field_separator'     => ',',
        'type'                => 'import',
        'allowed_errors'      => 0,
        'file'                => UploadedFile::fake()->create('product.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
        'filters'             => [
            'sku'    => 'demo-sku',
            'status' => '1',
        ],
    ];

    postJson(route('admin.settings.data_transfer.imports.store'), $payload)
        ->assertStatus(302)
        ->assertSessionHas('success');

    $job = JobInstances::query()->where('code', $code)->firstOrFail();

    // `filters` is cast to array on the model; the bracketed request keys are
    // what allow PHP to receive it as an associative array.
    expect($job->filters)->toBe([
        'sku'    => 'demo-sku',
        'status' => '1',
    ]);
});

it('brackets the filter input name for text/date/datetime/textarea in the blade source', function () {
    $file = dirname(__DIR__, 3)
        .'/src/Resources/views/components/data-transfer/filter-fields.blade.php';

    expect(file_exists($file))->toBeTrue();

    $contents = file_get_contents($file);

    // The fixed bracketed binding must be present ...
    expect($contents)->toContain(":name=\"'filters[' + filterField.name + ']'\"");

    // ... and the buggy bare binding must be gone.
    expect($contents)->not->toContain(':name="filterField.name"');
});
