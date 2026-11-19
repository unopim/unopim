<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\AttributeOption;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
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

const IDENTITY_SHARED_CODE = 'identity_shared_code';

const IDENTITY_OTHER_CODE = 'identity_other_code';

const IDENTITY_MISSING_CODE = 'identity_missing_code';

beforeEach(function (): void {
    config(['audit.enabled' => false]);
    Event::fake(['data_transfer.imports.batch.import.before', 'data_transfer.imports.batch.import.after']);

    $this->color = Attribute::factory()->create(['type' => 'text']);
    $this->size = Attribute::factory()->create(['type' => 'text']);

    $this->optionStorage = app(Storage::class);
    $attributes = app(AttributeStorage::class);
    $attributes->init();

    $locales = Mockery::mock(LocaleRepository::class);
    $locales->shouldReceive('getActiveLocales')->andReturn(collect([(object) ['code' => 'en_US'], (object) ['code' => 'fr_FR']]));
    $batches = Mockery::mock(JobTrackBatchRepository::class);
    $batches->shouldReceive('update')->andReturnUsing(fn (array $data, int $id): JobTrackBatch => new JobTrackBatch(['id' => $id]));

    $this->optionImporter = new Importer($batches, app(AttributeOptionRepository::class), $this->optionStorage, $locales, $attributes);
    $this->optionImporter->setErrorHelper(new Error);
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_APPEND]));
});

/**
 * Build an import batch for the given action and rows.
 */
function identityBatch(string $action, array $rows): JobTrackBatch
{
    $batch = new JobTrackBatch(['data' => $rows]);
    $batch->id = 1;
    $batch->setRelation('jobTrack', new JobTrack(['action' => $action]));

    return $batch;
}

/**
 * Build an import row for the shared option code.
 */
function identityRow(Attribute $attribute, string $locale = 'en_US', string $label = 'Shared'): array
{
    return ['code' => IDENTITY_SHARED_CODE, 'attribute_code' => $attribute->code, 'locale' => $locale, 'label' => $label];
}

/**
 * Persist an option with the given code for the attribute.
 */
function identityOption(Attribute $attribute, string $code = IDENTITY_SHARED_CODE): AttributeOption
{
    return AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => $code]);
}

/**
 * Count the persisted options that use the code.
 */
function identityOptionCount(string $code = IDENTITY_SHARED_CODE): int
{
    return DB::table('attribute_options')->where('code', $code)->count();
}

/**
 * Return the error codes recorded for a row.
 */
function identityErrorCodes(Error $errors, int $rowNumber = 1): array
{
    return array_column($errors->getAllErrors()[$rowNumber] ?? [], 'code');
}

it('imports the same code independently for two attributes and merges each translation', function (): void {
    $rows = [
        identityRow($this->color, 'en_US', 'Color'),
        identityRow($this->size, 'en_US', 'Size'),
        identityRow($this->color, 'fr_FR', 'Couleur'),
        identityRow($this->size, 'fr_FR', 'Taille'),
    ];

    foreach ($rows as $number => $row) {
        expect($this->optionImporter->validateRow($row, $number + 1))->toBeTrue();
    }

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_APPEND, $rows));

    expect(identityOptionCount())->toBe(2)
        ->and($this->optionImporter->getCreatedItemsCount())->toBe(2);

    foreach ([[$this->color, ['en_US' => 'Color', 'fr_FR' => 'Couleur']], [$this->size, ['en_US' => 'Size', 'fr_FR' => 'Taille']]] as [$attribute, $labels]) {
        $id = DB::table('attribute_options')->where('code', IDENTITY_SHARED_CODE)->where('attribute_id', $attribute->id)->value('id');

        expect(DB::table('attribute_option_translations')->where('attribute_option_id', $id)->orderBy('locale')->pluck('label', 'locale')->all())->toBe($labels);
    }
});

it('updates only the requested attribute and preserves its translated rows', function (): void {
    $color = identityOption($this->color);
    $size = identityOption($this->size);

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_APPEND, [
        identityRow($this->color, 'en_US', 'Updated'),
        identityRow($this->color, 'fr_FR', 'Modifie'),
    ]));

    expect((int) DB::table('attribute_options')->where('id', $color->id)->value('attribute_id'))->toBe($this->color->id)
        ->and((int) DB::table('attribute_options')->where('id', $size->id)->value('attribute_id'))->toBe($this->size->id)
        ->and(DB::table('attribute_option_translations')->where('attribute_option_id', $color->id)->orderBy('locale')->pluck('label', 'locale')->all())->toBe(['en_US' => 'Updated', 'fr_FR' => 'Modifie'])
        ->and(DB::table('attribute_option_translations')->where('attribute_option_id', $size->id)->count())->toBe(0)
        ->and($this->optionImporter->getUpdatedItemsCount())->toBe(1);
});

it('accepts a new option code already used by another attribute', function (): void {
    identityOption($this->size);

    expect($this->optionImporter->validateRow(identityRow($this->color), 1))->toBeTrue();

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_APPEND, [identityRow($this->color)]));

    expect(identityOptionCount())->toBe(2);
});

it('deletes only the specified attribute option', function (): void {
    identityOption($this->color);
    $size = identityOption($this->size);
    $row = ['code' => IDENTITY_SHARED_CODE, 'attribute_code' => $this->color->code];

    $this->optionStorage->init();
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));

    expect($this->optionImporter->validateRow($row, 1))->toBeTrue();

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [$row]));

    expect(DB::table('attribute_options')->where('code', IDENTITY_SHARED_CODE)->pluck('id')->map(fn ($id): int => (int) $id)->all())->toBe([$size->id])
        ->and($this->optionImporter->getDeletedItemsCount())->toBe(1);
});

it('rejects an ambiguous legacy delete with a dedicated error and leaves all matching options untouched', function (): void {
    identityOption($this->color);
    identityOption($this->size);
    $errors = new Error;
    $row = ['code' => IDENTITY_SHARED_CODE];

    $this->optionStorage->init();
    $this->optionImporter->setErrorHelper($errors);
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));

    expect($this->optionImporter->validateRow($row, 1))->toBeFalse()
        ->and(identityErrorCodes($errors))->toBe([Importer::ERROR_AMBIGUOUS_CODE_FOR_DELETE])
        ->and($errors->getAllErrors()[1][0]['message'])->toBe(trans('data_transfer::app.importers.attribute-options.validation.errors.ambiguous-code-to-delete', ['code' => IDENTITY_SHARED_CODE]));

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [$row]));

    expect(identityOptionCount())->toBe(2)
        ->and($this->optionImporter->getDeletedItemsCount())->toBe(0);
});

it('reports a missing code-only delete as not found', function (): void {
    $errors = new Error;

    $this->optionStorage->init();
    $this->optionImporter->setErrorHelper($errors);
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));

    expect($this->optionImporter->validateRow(['code' => IDENTITY_MISSING_CODE], 1))->toBeFalse()
        ->and(identityErrorCodes($errors))->toBe([Importer::ERROR_CODE_NOT_FOUND_FOR_DELETE]);
});

it('retains unambiguous code-only deletes', function (): void {
    identityOption($this->color);
    $row = ['code' => IDENTITY_SHARED_CODE];

    $this->optionStorage->init();
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));

    expect($this->optionImporter->validateRow($row, 1))->toBeTrue();

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [$row]));

    expect(identityOptionCount())->toBe(0);
});

it('never falls back to a global code when the delete attribute has no matching option', function (string $scope): void {
    identityOption($this->color);
    $errors = new Error;
    $row = ['code' => IDENTITY_SHARED_CODE, 'attribute_code' => $scope === 'other attribute' ? $this->size->code : $scope];

    $this->optionStorage->init();
    $this->optionImporter->setErrorHelper($errors);
    $this->optionImporter->setImport(new JobTrack(['action' => Import::ACTION_DELETE]));

    expect($this->optionImporter->validateRow($row, 1))->toBeFalse()
        ->and(identityErrorCodes($errors))->toBe([Importer::ERROR_CODE_NOT_FOUND_FOR_DELETE]);

    $this->optionImporter->importBatch(identityBatch(Import::ACTION_DELETE, [$row]));

    expect(identityOptionCount())->toBe(1);
})->with([
    'unknown attribute'      => 'identity_missing_attribute',
    'numeric attribute code' => '0',
    'another attribute'      => 'other attribute',
]);

it('loads scoped identities and resets stale storage on initialization', function (): void {
    $color = identityOption($this->color);
    $size = identityOption($this->size);
    $other = identityOption($this->color, IDENTITY_OTHER_CODE);

    $this->optionStorage->load([IDENTITY_SHARED_CODE]);

    expect($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->color->id))->toBe($color->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->size->id))->toBe($size->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE))->toBeNull()
        ->and($this->optionStorage->isAmbiguous(IDENTITY_SHARED_CODE))->toBeTrue()
        ->and($this->optionStorage->has(IDENTITY_OTHER_CODE))->toBeFalse();

    DB::table('attribute_options')->where('id', $color->id)->delete();
    $this->optionStorage->init();

    expect($this->optionStorage->has(IDENTITY_SHARED_CODE, $this->color->id))->toBeFalse()
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE))->toBe($size->id)
        ->and($this->optionStorage->isAmbiguous(IDENTITY_SHARED_CODE))->toBeFalse()
        ->and($this->optionStorage->get(IDENTITY_OTHER_CODE, $this->color->id))->toBe($other->id);

    $this->optionStorage->load([IDENTITY_OTHER_CODE]);

    expect($this->optionStorage->has(IDENTITY_SHARED_CODE, $this->size->id))->toBeFalse()
        ->and($this->optionStorage->get(IDENTITY_OTHER_CODE, $this->color->id))->toBe($other->id);
});

it('resolves persisted identities for legacy two-argument writes without crossing attributes', function (): void {
    $color = identityOption($this->color);
    $size = identityOption($this->size);

    expect($this->optionStorage->set(IDENTITY_SHARED_CODE, $color->id))->toBe($this->optionStorage)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->color->id))->toBe($color->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->size->id))->toBeNull()
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE))->toBe($color->id);

    expect($this->optionStorage->set(IDENTITY_SHARED_CODE, $size->id))->toBe($this->optionStorage)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->color->id))->toBe($color->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->size->id))->toBe($size->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE))->toBeNull();
});

it('leaves storage unchanged for invalid legacy identities', function (string $code, string $identity): void {
    $color = identityOption($this->color);
    $size = identityOption($this->size);
    $other = identityOption($this->color, IDENTITY_OTHER_CODE);
    $id = match ($identity) {
        'unknown' => (int) DB::table('attribute_options')->max('id') + 1000,
        'other'   => $other->id,
        default   => $color->id,
    };

    expect($this->optionStorage->set($code, $id))->toBe($this->optionStorage)
        ->and($this->optionStorage->isEmpty())->toBe(1);

    $this->optionStorage->load([IDENTITY_SHARED_CODE, IDENTITY_OTHER_CODE, IDENTITY_MISSING_CODE]);

    expect($this->optionStorage->set($code, $id))->toBe($this->optionStorage)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->color->id))->toBe($color->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->size->id))->toBe($size->id)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE))->toBeNull()
        ->and($this->optionStorage->get(IDENTITY_OTHER_CODE, $this->color->id))->toBe($other->id)
        ->and($this->optionStorage->get(IDENTITY_OTHER_CODE, $this->size->id))->toBeNull()
        ->and($this->optionStorage->get(IDENTITY_MISSING_CODE))->toBeNull();
})->with([
    'unknown ID'          => [IDENTITY_SHARED_CODE, 'unknown'],
    'wrong existing code' => [IDENTITY_SHARED_CODE, 'other'],
    'unknown code'        => [IDENTITY_MISSING_CODE, 'color'],
]);

it('keeps newly stored options scoped and makes duplicate code-only lookups ambiguous', function (): void {
    expect($this->optionStorage->isEmpty())->toBe(1);

    $this->optionStorage->set(IDENTITY_SHARED_CODE, 11, $this->color->id);

    expect($this->optionStorage->get(IDENTITY_SHARED_CODE))->toBe(11)
        ->and($this->optionStorage->isAmbiguous(IDENTITY_SHARED_CODE))->toBeFalse();

    $this->optionStorage->set(IDENTITY_SHARED_CODE, 22, $this->size->id);

    expect($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->color->id))->toBe(11)
        ->and($this->optionStorage->get(IDENTITY_SHARED_CODE, $this->size->id))->toBe(22)
        ->and($this->optionStorage->has(IDENTITY_SHARED_CODE))->toBeFalse()
        ->and($this->optionStorage->isAmbiguous(IDENTITY_SHARED_CODE))->toBeTrue()
        ->and($this->optionStorage->get(IDENTITY_MISSING_CODE, $this->color->id))->toBeNull();
});
