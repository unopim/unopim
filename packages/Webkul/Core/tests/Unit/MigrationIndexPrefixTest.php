<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$redundantIndexMigrations = [
    'packages/Webkul/Attribute/src/Database/Migrations/2026_07_31_103100_drop_redundant_code_index_from_attributes_table.php',
    'packages/Webkul/Attribute/src/Database/Migrations/2026_07_31_110000_drop_prefix_redundant_indexes_from_attribute_tables.php',
    'packages/Webkul/Category/src/Database/Migrations/2026_07_31_101500_drop_redundant_code_index_from_categories_table.php',
    'packages/Webkul/Category/src/Database/Migrations/2026_07_31_103200_drop_redundant_code_index_from_category_fields_table.php',
    'packages/Webkul/Category/src/Database/Migrations/2026_07_31_110200_drop_prefix_redundant_index_from_category_field_translations.php',
    'packages/Webkul/Product/src/Database/Migrations/2026_07_31_103000_drop_redundant_indexes_from_product_tables.php',
    'packages/Webkul/Product/src/Database/Migrations/2026_07_31_110100_drop_prefix_redundant_indexes_from_product_tables.php',
];

function probeTableIndexes(): array
{
    Schema::dropIfExists('index_prefix_probe');

    Schema::create('index_prefix_probe', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->unique('code');
        $table->index('code');
    });

    return Schema::getIndexes('index_prefix_probe');
}

function probePlainIndexName(array $indexes): ?string
{
    foreach ($indexes as $index) {
        if ($index['columns'] === ['code'] && ! $index['unique'] && ! $index['primary']) {
            return $index['name'];
        }
    }

    return null;
}

it('gives an auto generated index the table prefix', function () {
    $prefix = DB::connection()->getTablePrefix();

    $name = probePlainIndexName(probeTableIndexes());

    Schema::dropIfExists('index_prefix_probe');

    expect($name)->toBe($prefix.'index_prefix_probe_code_index');
});

it('cannot find a prefixed index by its unprefixed name', function () {
    $prefix = DB::connection()->getTablePrefix();

    probeTableIndexes();

    $unprefixed = Schema::hasIndex('index_prefix_probe', 'index_prefix_probe_code_index');
    $prefixed = Schema::hasIndex('index_prefix_probe', $prefix.'index_prefix_probe_code_index');

    Schema::dropIfExists('index_prefix_probe');

    expect($unprefixed)->toBeFalse()
        ->and($prefixed)->toBeTrue();
})->skip(fn (): bool => DB::connection()->getTablePrefix() === '', 'needs a table prefix');

it('separates the plain index from a unique index on the same column', function () {
    $indexes = probeTableIndexes();

    Schema::dropIfExists('index_prefix_probe');

    $onCode = array_values(array_filter($indexes, fn (array $index): bool => $index['columns'] === ['code']));

    expect($onCode)->toHaveCount(2)
        ->and(array_column($onCode, 'unique'))->toContain(true, false);
});

it('resolves redundant indexes by column instead of by name', function (string $path) {
    $source = file_get_contents(base_path($path));

    expect($source)->toContain('getIndexes(')
        ->and(preg_match("/hasIndex\([^)]*'/", $source))->toBe(0)
        ->and(preg_match("/dropIndex\('/", $source))->toBe(0);
})->with($redundantIndexMigrations);

it('restores a dropped index under the name the schema already used', function (string $path) {
    $source = file_get_contents(base_path($path));

    if (str_contains($source, "'name' =>")) {
        expect($source)->toContain("index(\$definition['columns'], \$definition['name'])");

        return;
    }

    expect(preg_match("/->index\([^)]*,\s*'[a-z0-9_]+'\)/i", $source))->toBe(0);
})->with($redundantIndexMigrations);
