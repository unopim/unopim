<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Storage as AttributeStorage;
use Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer;
use Webkul\DataTransfer\Helpers\Importers\AttributeOption\Storage;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

beforeEach(function () {
    $this->originalConnection = DB::getDefaultConnection();
    config(['database.connections.option_identity' => [
        'driver'                  => 'sqlite',
        'database'                => ':memory:',
        'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('option_identity');
    config(['audit.enabled' => false]);
    Event::fake(['data_transfer.imports.batch.import.before', 'data_transfer.imports.batch.import.after']);

    (require base_path('packages/Webkul/Core/src/Database/Migrations/2018_07_10_055143_create_locales_table.php'))->up();
    DB::table('locales')->insert([
        ['code' => 'en', 'status' => 1],
        ['code' => 'fr', 'status' => 1],
    ]);

    Schema::create('attributes', function (Blueprint $table) {
        $table->increments('id');
        $table->string('code')->unique();
    });
    foreach (['2018_07_05_140832_create_attribute_options_table.php', '2018_07_05_140856_create_attribute_option_translations_table.php'] as $migration) {
        (require base_path('packages/Webkul/Attribute/src/Database/Migrations/'.$migration))->up();
    }
    DB::table('attributes')->insert([['id' => 1, 'code' => 'color'], ['id' => 2, 'code' => 'size']]);

    $this->optionStorage = app(Storage::class);
    $attributes = app(AttributeStorage::class);
    $attributes->init();
    $locales = Mockery::mock(LocaleRepository::class);
    $locales->shouldReceive('getActiveLocales')->andReturn(collect([(object) ['code' => 'en'], (object) ['code' => 'fr']]));
    $batches = Mockery::mock(JobTrackBatchRepository::class);
    $batches->shouldReceive('update')->andReturnUsing(fn ($data, $id) => new JobTrackBatch(['id' => $id]));
    $this->optionImporter = new Importer($batches, app(AttributeOptionRepository::class), $this->optionStorage, $locales, $attributes);
    $this->optionImporter->setErrorHelper(new Error);
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_APPEND]));
});

afterEach(function () {
    DB::setDefaultConnection($this->originalConnection);
    DB::purge('option_identity');
});

function identityBatch(string $action, array $rows): JobTrackBatch
{
    $batch = new JobTrackBatch(['data' => $rows]);
    $batch->id = 1;
    $batch->setRelation('jobTrack', new JobTrack(['action' => $action]));

    return $batch;
}

function identityRow(string $attribute, string $locale = 'en', string $label = 'Shared'): array
{
    return ['code' => 'shared', 'attribute_code' => $attribute, 'locale' => $locale, 'label' => $label];
}

function seedIdentityOptions(): void
{
    DB::table('attribute_options')->insert([
        ['id' => 11, 'attribute_id' => 1, 'code' => 'shared', 'sort_order' => 1],
        ['id' => 22, 'attribute_id' => 2, 'code' => 'shared', 'sort_order' => 2],
    ]);
}

it('imports the same code independently for two attributes and merges each translation', function () {
    $rows = [identityRow('color', 'en', 'Color'), identityRow('size', 'en', 'Size'), identityRow('color', 'fr', 'Couleur'), identityRow('size', 'fr', 'Taille')];
    foreach ($rows as $number => $row) {
        expect($this->optionImporter->validateRow($row, $number + 1))->toBeTrue();
    }
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_APPEND, $rows));

    expect(DB::table('attribute_options')->count())->toBe(2)
        ->and($this->optionImporter->getCreatedItemsCount())->toBe(2);
    foreach ([1 => ['en' => 'Color', 'fr' => 'Couleur'], 2 => ['en' => 'Size', 'fr' => 'Taille']] as $attributeId => $labels) {
        $id = DB::table('attribute_options')->where('attribute_id', $attributeId)->value('id');
        expect(DB::table('attribute_option_translations')->where('attribute_option_id', $id)->pluck('label', 'locale')->all())->toBe($labels);
    }
});

it('updates only the requested attribute and preserves its translated rows', function () {
    seedIdentityOptions();
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_APPEND, [identityRow('color', 'en', 'Updated'), identityRow('color', 'fr', 'Modifie')]));

    expect(DB::table('attribute_options')->where('id', 11)->value('attribute_id'))->toBe(1)
        ->and(DB::table('attribute_options')->where('id', 22)->value('attribute_id'))->toBe(2)
        ->and(DB::table('attribute_option_translations')->where('attribute_option_id', 11)->pluck('label', 'locale')->all())->toBe(['en' => 'Updated', 'fr' => 'Modifie'])
        ->and(DB::table('attribute_option_translations')->where('attribute_option_id', 22)->count())->toBe(0)
        ->and($this->optionImporter->getUpdatedItemsCount())->toBe(1);
});

it('accepts a new option code already used by another attribute', function () {
    DB::table('attribute_options')->insert(['id' => 22, 'attribute_id' => 2, 'code' => 'shared']);
    expect($this->optionImporter->validateRow(identityRow('color'), 1))->toBeTrue();
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_APPEND, [identityRow('color')]));
    expect(DB::table('attribute_options')->count())->toBe(2);
});

it('deletes only the specified attribute option', function () {
    seedIdentityOptions();
    $this->optionStorage->init();
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));
    expect($this->optionImporter->validateRow(['code' => 'shared', 'attribute_code' => 'color'], 1))->toBeTrue();
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [['code' => 'shared', 'attribute_code' => 'color']]));
    expect(DB::table('attribute_options')->pluck('id')->all())->toBe([22])
        ->and($this->optionImporter->getDeletedItemsCount())->toBe(1);
});

it('rejects an ambiguous legacy delete and leaves all matching options untouched', function () {
    seedIdentityOptions();
    $this->optionStorage->init();
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));
    expect($this->optionImporter->validateRow(['code' => 'shared'], 1))->toBeFalse();
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [['code' => 'shared']]));
    expect(DB::table('attribute_options')->count())->toBe(2)
        ->and($this->optionImporter->getDeletedItemsCount())->toBe(0);
});

it('retains unambiguous code-only deletes', function () {
    DB::table('attribute_options')->insert(['id' => 11, 'attribute_id' => 1, 'code' => 'shared']);
    $this->optionStorage->init();
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));
    expect($this->optionImporter->validateRow(['code' => 'shared'], 1))->toBeTrue();
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [['code' => 'shared']]));
    expect(DB::table('attribute_options')->count())->toBe(0);
});

it('never falls back to a global code when the delete attribute has no matching option', function (string $attributeCode) {
    DB::table('attribute_options')->insert(['id' => 11, 'attribute_id' => 1, 'code' => 'shared']);
    $this->optionStorage->init();
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));
    expect($this->optionImporter->validateRow(['code' => 'shared', 'attribute_code' => $attributeCode], 1))->toBeFalse();
    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [['code' => 'shared', 'attribute_code' => $attributeCode]]));
    expect(DB::table('attribute_options')->count())->toBe(1);
})->with(['unknown attribute' => 'missing', 'numeric attribute code' => '0', 'another existing attribute' => 'size']);

it('loads scoped identities and resets stale storage on initialization', function () {
    seedIdentityOptions();
    DB::table('attribute_options')->insert(['id' => 33, 'attribute_id' => 1, 'code' => 'other']);
    $this->optionStorage->load(['shared']);
    expect($this->optionStorage->get('shared', 1))->toBe(11)
        ->and($this->optionStorage->get('shared', 2))->toBe(22)
        ->and($this->optionStorage->get('shared'))->toBeNull()
        ->and($this->optionStorage->has('other'))->toBeFalse();
    DB::table('attribute_options')->where('id', 11)->delete();
    $this->optionStorage->init();
    expect($this->optionStorage->has('shared', 1))->toBeFalse()
        ->and($this->optionStorage->get('shared'))->toBe(22)
        ->and($this->optionStorage->get('other', 1))->toBe(33);

    $this->optionStorage->load(['other']);
    expect($this->optionStorage->has('shared', 2))->toBeFalse()
        ->and($this->optionStorage->get('other', 1))->toBe(33);
});

it('resolves persisted identities for legacy two-argument writes without crossing attributes', function () {
    seedIdentityOptions();

    expect($this->optionStorage->set('shared', 11))->toBe($this->optionStorage)
        ->and($this->optionStorage->get('shared', 1))->toBe(11)
        ->and($this->optionStorage->get('shared', 2))->toBeNull()
        ->and($this->optionStorage->get('shared'))->toBe(11);

    expect($this->optionStorage->set('shared', 22))->toBe($this->optionStorage)
        ->and($this->optionStorage->get('shared', 1))->toBe(11)
        ->and($this->optionStorage->get('shared', 2))->toBe(22)
        ->and($this->optionStorage->get('shared'))->toBeNull();
});

it('leaves storage unchanged for invalid legacy identities', function (string $code, int $id) {
    seedIdentityOptions();
    DB::table('attribute_options')->insert(['id' => 33, 'attribute_id' => 1, 'code' => 'other']);

    expect($this->optionStorage->set($code, $id))->toBe($this->optionStorage)
        ->and($this->optionStorage->isEmpty())->toBe(1);

    $this->optionStorage->init();

    expect($this->optionStorage->set($code, $id))->toBe($this->optionStorage)
        ->and($this->optionStorage->get('shared', 1))->toBe(11)
        ->and($this->optionStorage->get('shared', 2))->toBe(22)
        ->and($this->optionStorage->get('shared'))->toBeNull()
        ->and($this->optionStorage->get('other', 1))->toBe(33)
        ->and($this->optionStorage->get('other', 2))->toBeNull()
        ->and($this->optionStorage->get('missing'))->toBeNull();
})->with([
    'unknown ID'          => ['shared', 999],
    'wrong existing code' => ['shared', 33],
    'unknown code'        => ['missing', 11],
]);

it('keeps newly stored options scoped and makes duplicate code-only lookups ambiguous', function () {
    expect($this->optionStorage->isEmpty())->toBe(1);
    $this->optionStorage->set('shared', 11, 1);
    expect($this->optionStorage->get('shared'))->toBe(11);
    $this->optionStorage->set('shared', 22, 2);
    expect($this->optionStorage->get('shared', 1))->toBe(11)
        ->and($this->optionStorage->get('shared', 2))->toBe(22)
        ->and($this->optionStorage->has('shared'))->toBeFalse()
        ->and($this->optionStorage->get('missing', 1))->toBeNull();
});
