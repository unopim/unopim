<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Database\Schema\PrefixedIndexNameBlueprint;

afterEach(function () {
    Schema::dropIfExists('named_index_probe_children');
    Schema::dropIfExists('named_index_probe');
});

function createNamedIndexProbe(): void
{
    Schema::create('named_index_probe', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->string('label');
        $table->index('code', 'probe_code_named_index');
    });
}

function indexNames(string $table): array
{
    return array_map(strtolower(...), array_column(Schema::getIndexes($table), 'name'));
}

function reportedName(string $index): string
{
    return strtolower(PrefixedIndexNameBlueprint::nameFor(DB::getTablePrefix(), $index));
}

it('builds every schema blueprint as the prefixed index name blueprint', function () {
    Schema::create('named_index_probe', function (Blueprint $table): void {
        expect($table)->toBeInstanceOf(PrefixedIndexNameBlueprint::class);

        $table->id();
    });
});

it('leaves an explicit index name unchanged without a table prefix', function () {
    expect(PrefixedIndexNameBlueprint::nameFor('', 'unique_code_attribute_id_index'))
        ->toBe('unique_code_attribute_id_index');
});

it('puts the table prefix in front of an explicit index name', function () {
    expect(PrefixedIndexNameBlueprint::nameFor('3X_', 'unique_code_attribute_id_index'))
        ->toBe('3X_unique_code_attribute_id_index');
});

it('shortens a prefixed index name to the identifier limit the same way every time', function () {
    $name = 'attribute_family_translations_name_locale_attribute_family_id';

    $first = PrefixedIndexNameBlueprint::nameFor('3XL_', $name);

    expect(strlen($first))->toBe(PrefixedIndexNameBlueprint::MAX_INDEX_NAME_LENGTH)
        ->and($first)->toStartWith('3XL_')
        ->and(PrefixedIndexNameBlueprint::nameFor('3XL_', $name))->toBe($first)
        ->and(PrefixedIndexNameBlueprint::nameFor('XL_', $name))->not->toBe($first);
});

it('creates an explicitly named index under the prefixed name', function () {
    createNamedIndexProbe();

    expect(indexNames('named_index_probe'))
        ->toContain(reportedName('probe_code_named_index'));
});

it('drops an explicitly named index by the name the migration used', function () {
    createNamedIndexProbe();

    Schema::table('named_index_probe', fn (Blueprint $table) => $table->dropIndex('probe_code_named_index'));

    expect(indexNames('named_index_probe'))
        ->not->toContain(reportedName('probe_code_named_index'));
});

it('drops an index created before index names carried the table prefix', function () {
    createNamedIndexProbe();

    $grammar = DB::getQueryGrammar();

    DB::statement('CREATE INDEX '.$grammar->wrap('probe_label_legacy_index').' ON '.$grammar->wrapTable('named_index_probe').' ('.$grammar->wrap('label').')');

    Schema::table('named_index_probe', fn (Blueprint $table) => $table->dropIndex('probe_label_legacy_index'));

    expect(indexNames('named_index_probe'))->not->toContain('probe_label_legacy_index');
});

it('renames an explicitly named index to a prefixed name', function () {
    createNamedIndexProbe();

    Schema::table('named_index_probe', fn (Blueprint $table) => $table->renameIndex('probe_code_named_index', 'probe_code_renamed_index'));

    expect(indexNames('named_index_probe'))
        ->toContain(reportedName('probe_code_renamed_index'))
        ->not->toContain(reportedName('probe_code_named_index'));
});

it('creates and drops an explicitly named foreign key under the prefixed name', function () {
    createNamedIndexProbe();

    Schema::create('named_index_probe_children', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('probe_id');
        $table->foreign('probe_id', 'probe_children_probe_foreign')->references('id')->on('named_index_probe');
    });

    $prefixedName = reportedName('probe_children_probe_foreign');

    expect(array_map(strtolower(...), array_column(Schema::getForeignKeys('named_index_probe_children'), 'name')))->toContain($prefixedName);

    Schema::table('named_index_probe_children', fn (Blueprint $table) => $table->dropForeign('probe_children_probe_foreign'));

    expect(array_map(strtolower(...), array_column(Schema::getForeignKeys('named_index_probe_children'), 'name')))->not->toContain($prefixedName);
});
