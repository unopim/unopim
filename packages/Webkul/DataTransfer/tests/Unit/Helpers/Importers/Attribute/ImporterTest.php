<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\Attribute;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Importer;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

function makeAttributeImporter(array $overrides = []): array
{
    $batchRepo = $overrides['batchRepo'] ?? mock(JobTrackBatchRepository::class);
    $attributeRepo = $overrides['attributeRepo'] ?? mock(AttributeRepository::class);
    $attributeStorage = $overrides['storage'] ?? mock(Storage::class);
    $localeRepo = $overrides['localeRepo'] ?? mock(LocaleRepository::class);
    $errorHelper = $overrides['errorHelper'] ?? mock(Error::class);

    // Default: one active locale
    $localeRepo->shouldReceive('getActiveLocales')
        ->andReturn(collect([(object) ['code' => 'en']]));

    $attributeStorage->shouldReceive('init')->byDefault();
    $attributeStorage->shouldReceive('load')->byDefault();

    // Wire up errorHelper defaults so AbstractImporter never hits null
    $errorHelper->shouldReceive('addErrorMessage')->byDefault();
    $errorHelper->shouldReceive('addError')->byDefault();
    $errorHelper->shouldReceive('addRowToSkip')->byDefault();
    // Default: row is valid (no errors). Override per-test when you need invalid.
    $errorHelper->shouldReceive('isRowInvalid')->andReturn(false)->byDefault();

    $importer = new Importer($batchRepo, $attributeRepo, $attributeStorage, $localeRepo);
    $importer->setErrorHelper($errorHelper);

    return compact('importer', 'batchRepo', 'attributeRepo', 'attributeStorage', 'localeRepo', 'errorHelper');
}

/**
 * Build a mock JobTrack (the "import" object) with the given action.
 *
 * setImport() is typed to JobTrackContract — a plain stdClass will cause a
 * TypeError. We must use a proper mock of the contract instead.
 */
function makeImport(string $action): JobTrackContract
{
    $import = mock(JobTrackContract::class)->makePartial();
    $import->action = $action;

    return $import;
}

/**
 * Build a minimal mock batch whose jobTrack property is also a contract mock.
 */
function makeAttributeImportBatch(string $action, array $data = []): JobTrackBatchContract
{
    $jobTrack = mock(JobTrackContract::class)->makePartial();
    $jobTrack->action = $action;

    $batch = mock(JobTrackBatchContract::class)->makePartial();
    $batch->jobTrack = $jobTrack;
    $batch->data = $data;
    $batch->id = 1;

    return $batch;
}

describe('validateRow', function () {

    it('skips row when action is DELETE and code does not exist in storage', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('non_existent')->andReturn(null);

        $result = $importer->validateRow(['code' => 'non_existent'], 1);

        expect($result)->toBeFalse();
    });

    it('skips row when action is DELETE and code is "sku" (system attribute)', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('sku')->andReturn(5);

        $result = $importer->validateRow(['code' => 'sku'], 1);

        expect($result)->toBeFalse();
    });

    it('returns true when action is DELETE and code exists and is not "sku"', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('color')->andReturn(10);

        $result = $importer->validateRow(['code' => 'color'], 1);

        expect($result)->toBeTrue();
    });

    it('fails validation when locale is missing', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        // After skipRow is called the row will be marked invalid
        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        $result = $importer->validateRow(['code' => 'size', 'locale' => ''], 1);

        expect($result)->toBeFalse();
    });

    it('fails validation when locale is not in active locales', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        $result = $importer->validateRow(['code' => 'size', 'locale' => 'zz'], 1);

        expect($result)->toBeFalse();
    });

    it('fails validation when code is missing on insert', function () {
        ['importer' => $importer, 'attributeStorage' => $storage, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('')->andReturn(false);

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        $result = $importer->validateRow(['code' => '', 'locale' => 'en', 'type' => 'text'], 1);

        expect($result)->toBeFalse();
    });

    it('fails validation when type is missing on insert', function () {
        ['importer' => $importer, 'attributeStorage' => $storage, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('new_attr')->andReturn(false);

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        $result = $importer->validateRow(['code' => 'new_attr', 'locale' => 'en'], 1);

        expect($result)->toBeFalse();
    });

    it('fails validation when type is an invalid value', function () {
        ['importer' => $importer, 'attributeStorage' => $storage, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('new_attr')->andReturn(false);

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        $result = $importer->validateRow(
            ['code' => 'new_attr', 'locale' => 'en', 'type' => 'invalid_type'],
            1,
        );

        expect($result)->toBeFalse();
    });

    it('passes validation for a new attribute with valid data', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('brand')->andReturn(false);

        // Default isRowInvalid → false, so no override needed

        $result = $importer->validateRow(
            ['code' => 'brand', 'locale' => 'en', 'type' => 'text'],
            1,
        );

        expect($result)->toBeTrue();
    });

    it('passes validation for an existing attribute without type (update path)', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        // Attribute already exists → update path; type is optional
        $storage->shouldReceive('has')->with('color')->andReturn(true);

        $result = $importer->validateRow(
            ['code' => 'color', 'locale' => 'en'],
            1,
        );

        expect($result)->toBeTrue();
    });

    it('does not re-validate an already validated row', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('brand')->andReturn(false)->once();

        // First call validates; second call must return the cached result
        $importer->validateRow(['code' => 'brand', 'locale' => 'en', 'type' => 'text'], 1);
        $result = $importer->validateRow(['code' => 'brand', 'locale' => 'en', 'type' => 'text'], 1);

        expect($result)->toBeTrue();
    });
});

// ─── prepareAttributes ────────────────────────────────────────────────────────

describe('prepareAttributes', function () {

    it('places a new attribute into the insert bucket', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('material')->andReturn(false);

        $attributes = [];
        $importer->prepareAttributes(
            ['code' => 'material', 'locale' => 'en', 'type' => 'text', 'name' => 'Material'],
            $attributes,
        );

        expect($attributes)->toHaveKey('insert')
            ->and($attributes['insert'])->toHaveKey('material')
            ->and($attributes['insert']['material']['code'])->toBe('material')
            ->and($attributes['insert']['material']['type'])->toBe('text');
    });

    it('places an existing attribute into the update bucket', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('color')->andReturn(true);

        $attributes = [];
        $importer->prepareAttributes(
            ['code' => 'color', 'locale' => 'en', 'name' => 'Colour'],
            $attributes,
        );

        expect($attributes)->toHaveKey('update')
            ->and($attributes['update'])->toHaveKey('color');
    });

    it('merges multiple locale rows for the same attribute', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('size')->andReturn(false);

        $attributes = [];

        $importer->prepareAttributes(
            ['code' => 'size', 'locale' => 'en', 'type' => 'text', 'name' => 'Size'],
            $attributes,
        );
        $importer->prepareAttributes(
            ['code' => 'size', 'locale' => 'fr', 'type' => 'text', 'name' => 'Taille'],
            $attributes,
        );

        expect($attributes['insert']['size'])->toHaveKeys(['en', 'fr']);
    });

    it('casts boolean-like fields to integers', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('weight')->andReturn(false);

        $attributes = [];
        $importer->prepareAttributes(
            [
                'code'          => 'weight',
                'locale'        => 'en',
                'type'          => 'text',
                'is_required'   => '1',
                'is_unique'     => '0',
                'is_filterable' => 'true',
            ],
            $attributes,
        );

        $data = $attributes['insert']['weight'];
        expect($data['is_required'])->toBe(1)
            ->and($data['is_unique'])->toBe(0)
            ->and($data['is_filterable'])->toBe(1);
    });

    it('casts position to integer', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('rank')->andReturn(false);

        $attributes = [];
        $importer->prepareAttributes(
            ['code' => 'rank', 'locale' => 'en', 'type' => 'text', 'position' => '3'],
            $attributes,
        );

        expect($attributes['insert']['rank']['position'])->toBe(3);
    });

    it('omits fields that are empty strings', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('tag')->andReturn(false);

        $attributes = [];
        $importer->prepareAttributes(
            ['code' => 'tag', 'locale' => 'en', 'type' => 'text', 'swatch_type' => ''],
            $attributes,
        );

        expect($attributes['insert']['tag'])->not->toHaveKey('swatch_type');
    });
});

// ─── saveAttributes ───────────────────────────────────────────────────────────

describe('saveAttributes', function () {

    it('calls attributeRepository->create for each insert entry', function () {
        ['importer' => $importer, 'attributeRepo' => $repo, 'attributeStorage' => $storage] = makeImporter();

        $fakeModel = new stdClass;
        $fakeModel->id = 99;

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::subset(['code' => 'new_attr']))
            ->andReturn($fakeModel);

        $storage->shouldReceive('set')->with('new_attr', 99)->once();

        $importer->saveAttributes([
            'insert' => [
                'new_attr' => ['code' => 'new_attr', 'type' => 'text'],
            ],
        ]);

        expect($importer->getCreatedItemsCount())->toBe(1);
    });

    it('calls attributeRepository->update for each update entry', function () {
        ['importer' => $importer, 'attributeRepo' => $repo, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('get')->with('color')->andReturn(7);

        $repo->shouldReceive('update')
            ->once()
            ->with(Mockery::subset(['code' => 'color']), 7);

        $importer->saveAttributes([
            'update' => [
                'color' => ['code' => 'color', 'en' => ['name' => 'Colour']],
            ],
        ]);

        expect($importer->getUpdatedItemsCount())->toBe(1);
    });

    it('handles both insert and update in the same call', function () {
        ['importer' => $importer, 'attributeRepo' => $repo, 'attributeStorage' => $storage] = makeImporter();

        $fakeModel = new stdClass;
        $fakeModel->id = 50;

        $storage->shouldReceive('get')->with('color')->andReturn(7);
        $storage->shouldReceive('set')->with('brand', 50);

        $repo->shouldReceive('update')->once()->with(Mockery::any(), 7);
        $repo->shouldReceive('create')->once()->andReturn($fakeModel);

        $importer->saveAttributes([
            'update' => ['color' => ['code' => 'color']],
            'insert' => ['brand' => ['code' => 'brand', 'type' => 'text']],
        ]);

        expect($importer->getCreatedItemsCount())->toBe(1)
            ->and($importer->getUpdatedItemsCount())->toBe(1);
    });

    it('does nothing when attributes array is empty', function () {
        ['importer' => $importer, 'attributeRepo' => $repo] = makeImporter();

        $repo->shouldNotReceive('create');
        $repo->shouldNotReceive('update');

        $importer->saveAttributes([]);

        expect($importer->getCreatedItemsCount())->toBe(0)
            ->and($importer->getUpdatedItemsCount())->toBe(0);
    });
});

// ─── importBatch ─────────────────────────────────────────────────────────────

describe('importBatch', function () {

    it('fires before and after events and marks batch as processed', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'attributeStorage' => $storage, 'attributeRepo' => $repo] = makeImporter();

        $batch = makeAttributeImportBatch(Import::ACTION_APPEND, [
            ['code' => 'weight', 'locale' => 'en', 'type' => 'text', 'name' => 'Weight'],
        ]);

        $storage->shouldReceive('load')->with(['weight']);
        $storage->shouldReceive('has')->with('weight')->andReturn(false);
        $storage->shouldReceive('set')->with('weight', Mockery::any());

        $fakeModel = new stdClass;
        $fakeModel->id = 20;
        $repo->shouldReceive('create')->andReturn($fakeModel);

        $batchRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn ($data) => $data['state'] === Import::STATE_PROCESSED),
                1,
            )
            ->andReturn($batch);

        $result = $importer->importBatch($batch);

        expect($result)->toBeTrue();
        Event::assertDispatched('data_transfer.imports.batch.import.before');
        Event::assertDispatched('data_transfer.imports.batch.import.after');
    });

    it('deletes attributes when action is DELETE', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'attributeStorage' => $storage, 'attributeRepo' => $repo] = makeImporter();

        $batch = makeAttributeImportBatch(Import::ACTION_DELETE, [
            ['code' => 'obsolete'],
        ]);

        $storage->shouldReceive('load')->with(['obsolete']);
        $storage->shouldReceive('has')->with('obsolete')->andReturn(true);
        $storage->shouldReceive('get')->with('obsolete')->andReturn(42);

        $repo->shouldReceive('deleteWhere')
            ->once()
            ->with([['id', 'IN', [42]]]);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        $result = $importer->importBatch($batch);

        expect($result)->toBeTrue();
    });

    it('skips "sku" attribute during delete batch and deletes nothing', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'attributeStorage' => $storage, 'attributeRepo' => $repo] = makeImporter();

        $batch = makeAttributeImportBatch(Import::ACTION_DELETE, [
            ['code' => 'sku'],
        ]);

        $storage->shouldReceive('load')->with(['sku']);
        $storage->shouldReceive('has')->with('sku')->andReturn(true);
        $storage->shouldReceive('get')->with('sku')->andReturn(1);

        // deleteWhere must be called with an empty list — sku is a protected system attribute
        $repo->shouldReceive('deleteWhere')
            ->once()
            ->with([['id', 'IN', []]]);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        $result = $importer->importBatch($batch);

        // Batch returns true but the deleted count stays at zero
        expect($result)->toBeTrue()
            ->and($importer->getDeletedItemsCount())->toBe(0);
    });
});

// ─── isAttributeExist ────────────────────────────────────────────────────────

describe('isAttributeExist', function () {

    it('returns true when storage has the code', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('color')->andReturn(true);

        expect($importer->isAttributeExist('color'))->toBeTrue();
    });

    it('returns false when storage does not have the code', function () {
        ['importer' => $importer, 'attributeStorage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('missing')->andReturn(false);

        expect($importer->isAttributeExist('missing'))->toBeFalse();
    });
});
