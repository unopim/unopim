<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\Installer\Database\Seeders\Demo\DemoJobSeeder;

/**
 * The seeder once looked for samples one path segment short of the configured
 * `sample_path`, so every demo import profile landed with a null `file_path`
 * that cannot run and breaks Storage::delete() on edit.
 */
it('seeds every demo import profile with a sample file on the private disk', function () {
    Storage::fake('private');

    (new DemoJobSeeder)->run();

    $imports = JobInstances::query()->where('type', 'import')->get();

    expect($imports)->not->toBeEmpty();

    foreach ($imports as $import) {
        expect($import->file_path)
            ->not->toBeNull("demo import profile {$import->code} has no sample file");

        Storage::disk('private')->assertExists($import->file_path);
    }
});

it('resolves samples for every configured importer', function () {
    Storage::fake('private');

    (new DemoJobSeeder)->run();

    $seeded = JobInstances::query()
        ->where('type', 'import')
        ->pluck('entity_type')
        ->all();

    expect($seeded)->toEqualCanonicalizing(array_keys(config('importers', [])));
});
