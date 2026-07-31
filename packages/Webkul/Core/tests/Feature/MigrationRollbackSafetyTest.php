<?php

function migrationFiles(): array
{
    $files = [];

    foreach (glob(base_path('packages/Webkul/*/src/Database/Migrations/*.php')) as $file) {
        $files[basename($file)] = $file;
    }

    foreach (glob(base_path('database/migrations/*.php')) as $file) {
        $files[basename($file)] = $file;
    }

    return $files;
}

function migrationNamed(string $needle): string
{
    foreach (migrationFiles() as $name => $path) {
        if (str_contains($name, $needle)) {
            return file_get_contents($path);
        }
    }

    throw new RuntimeException("No migration matching {$needle}");
}

function downBody(string $source): string
{
    $start = strpos($source, 'function down()');

    expect($start)->not->toBeFalse();

    return substr($source, $start);
}

it('finds the migrations to inspect', function () {
    expect(migrationFiles())->not->toBeEmpty();
});

it('gives every migration a down method so a rollback can never stop halfway', function () {
    $missing = [];

    foreach (migrationFiles() as $name => $path) {
        if (! preg_match('/function\s+down\s*\(/', file_get_contents($path))) {
            $missing[] = $name;
        }
    }

    expect($missing)->toBe([]);
});

it('never passes a literal name to dropForeign, which silently skips the table prefix', function () {
    $offenders = [];

    foreach (migrationFiles() as $name => $path) {
        if (preg_match('/dropForeign\(\s*[\'"]/', file_get_contents($path))) {
            $offenders[] = $name;
        }
    }

    expect($offenders)->toBe([]);
});

it('detaches the foreign key before dropping an index that has become its only cover', function (string $migration, array $columns, string $index) {
    $down = downBody(migrationNamed($migration));

    $dropIndexAt = strpos($down, "dropIndex('{$index}')");

    expect($dropIndexAt)->not->toBeFalse();

    foreach ($columns as $column) {
        $dropForeignAt = strpos($down, "dropForeign(['{$column}'])");

        expect($dropForeignAt)->not->toBeFalse()
            ->and($dropForeignAt)->toBeLessThan($dropIndexAt);
    }

    expect($down)->toContain('->foreign(');
})->with([
    ['add_family_attribute_index_to_completeness_settings_table', ['family_id'], 'cs_family_attribute_idx'],
    ['add_family_lookup_indexes_to_attribute_mapping_tables', ['attribute_family_id'], 'afgm_family_idx'],
    ['add_covering_indexes_to_product_completeness_table', ['channel_id', 'locale_id'], 'pc_channel_product_idx'],
]);

it('restores every foreign key it detaches', function (string $migration, int $expected) {
    $down = downBody(migrationNamed($migration));

    expect(substr_count($down, 'dropForeign(['))->toBe($expected)
        ->and(substr_count($down, '->foreign('))->toBe($expected);
})->with([
    ['add_family_attribute_index_to_completeness_settings_table', 1],
    ['add_family_lookup_indexes_to_attribute_mapping_tables', 2],
    ['add_covering_indexes_to_product_completeness_table', 2],
]);
