<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\AttributeOption;

use Illuminate\Support\Facades\Event;
use stdClass;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Storage as AttributeStorage;
use Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer;
use Webkul\DataTransfer\Helpers\Importers\AttributeOption\Storage as AttributeOptionStorage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

/**
 * Helper to create importer with mocks
 */
function makeImporter(array $overrides = []): array
{
    $batchRepo = $overrides['batchRepo'] ?? mock(JobTrackBatchRepository::class);
    $optionRepo = $overrides['optionRepo'] ?? mock(AttributeOptionRepository::class);
    $storage = $overrides['storage'] ?? mock(AttributeOptionStorage::class);
    $attrStorage = $overrides['attrStorage'] ?? mock(AttributeStorage::class);
    $localeRepo = $overrides['localeRepo'] ?? mock(LocaleRepository::class);
    $errorHelper = $overrides['errorHelper'] ?? mock(Error::class);

    $localeRepo->shouldReceive('getActiveLocales')
        ->andReturn(collect([(object) ['code' => 'en']]));

    $storage->shouldReceive('init')->byDefault();
    $storage->shouldReceive('load')->byDefault();

    $attrStorage->shouldReceive('init')->byDefault();
    $attrStorage->shouldReceive('load')->byDefault();

    $errorHelper->shouldReceive('addErrorMessage')->byDefault();
    $errorHelper->shouldReceive('addError')->byDefault();
    $errorHelper->shouldReceive('addRowToSkip')->byDefault();
    $errorHelper->shouldReceive('isRowInvalid')->andReturn(false)->byDefault();

    $importer = new Importer($batchRepo, $optionRepo, $storage, $localeRepo, $attrStorage);
    $importer->setErrorHelper($errorHelper);

    return compact('importer', 'batchRepo', 'optionRepo', 'storage', 'attrStorage', 'localeRepo', 'errorHelper');
}

function makeImport(string $action): JobTrackContract
{
    $import = mock(JobTrackContract::class)->makePartial();
    $import->action = $action;

    return $import;
}

function makeAttributeOptionImportBatch(string $action, array $data = []): JobTrackBatchContract
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

    it('fails when deleting non-existing code', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('missing')->andReturn(null);

        expect($importer->validateRow(['code' => 'missing'], 1))->toBeFalse();
    });

    it('passes delete when code exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('opt1')->andReturn(10);

        expect($importer->validateRow(['code' => 'opt1'], 1))->toBeTrue();
    });

    it('fails when locale is missing', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'opt', 'locale' => ''], 1))->toBeFalse();
    });

    it('fails when attribute_code is missing', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'opt', 'locale' => 'en', 'attribute_code' => ''], 1))->toBeFalse();
    });

    it('fails when attribute_code does not exist', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper, 'attrStorage' => $attrStorage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $attrStorage->shouldReceive('has')->with('invalid_attr')->andReturn(false);
        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'opt', 'locale' => 'en', 'attribute_code' => 'invalid_attr'], 1))->toBeFalse();
    });

    it('passes valid insert row', function () {
        ['importer' => $importer, 'storage' => $storage, 'attrStorage' => $attrStorage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $attrStorage->shouldReceive('has')->with('color')->andReturn(true);
        $storage->shouldReceive('has')->with('opt1')->andReturn(false);

        expect($importer->validateRow(['code' => 'opt1', 'attribute_code' => 'color', 'locale' => 'en'], 1))->toBeTrue();
    });

    it('passes update row when already exists', function () {
        ['importer' => $importer, 'storage' => $storage, 'attrStorage' => $attrStorage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $attrStorage->shouldReceive('has')->with('color')->andReturn(true);
        $storage->shouldReceive('has')->with('opt1')->andReturn(true);

        expect($importer->validateRow(['code' => 'opt1', 'attribute_code' => 'color', 'locale' => 'en'], 1))->toBeTrue();
    });
});

describe('prepareAttributeOptions', function () {

    it('adds new option to insert', function () {
        ['importer' => $importer, 'storage' => $storage, 'attrStorage' => $attrStorage] = makeImporter();

        $storage->shouldReceive('has')->with('opt')->andReturn(false);
        $attrStorage->shouldReceive('get')->with('color')->andReturn(5);

        $data = [];

        $importer->prepareAttributeOptions(
            ['code' => 'opt', 'attribute_code' => 'color', 'locale' => 'en', 'label' => 'Option'],
            $data
        );

        expect($data)->toHaveKey('insert')
            ->and($data['insert'])->toHaveKey('opt')
            ->and($data['insert']['opt']['attribute_id'])->toEqual(5);
    });

    it('adds existing option to update', function () {
        ['importer' => $importer, 'storage' => $storage, 'attrStorage' => $attrStorage] = makeImporter();

        $storage->shouldReceive('has')->with('opt')->andReturn(true);
        $attrStorage->shouldReceive('get')->with('color')->andReturn(5);

        $data = [];

        $importer->prepareAttributeOptions(
            ['code' => 'opt', 'attribute_code' => 'color', 'locale' => 'en', 'label' => 'Option'],
            $data
        );

        expect($data)->toHaveKey('update');
    });

    it('merges locales', function () {
        ['importer' => $importer, 'storage' => $storage, 'attrStorage' => $attrStorage] = makeImporter();

        $storage->shouldReceive('has')->with('opt')->andReturn(false);
        $attrStorage->shouldReceive('get')->with('color')->andReturn(5);

        $data = [];

        $importer->prepareAttributeOptions(['code' => 'opt', 'attribute_code' => 'color', 'locale' => 'en', 'label' => 'Option'], $data);
        $importer->prepareAttributeOptions(['code' => 'opt', 'attribute_code' => 'color', 'locale' => 'fr', 'label' => 'Option Fr'], $data);

        expect($data['insert']['opt'])->toHaveKeys(['en', 'fr']);
    });
});

describe('saveAttributeOptions', function () {

    it('creates new options', function () {
        ['importer' => $importer, 'optionRepo' => $repo, 'storage' => $storage] = makeImporter();

        $model = new stdClass;
        $model->id = 5;

        $repo->shouldReceive('create')->once()->andReturn($model);
        $storage->shouldReceive('set')->with('opt', 5);

        $importer->saveAttributeOptions([
            'insert' => ['opt' => ['code' => 'opt']],
        ]);

        expect($importer->getCreatedItemsCount())->toBe(1);
    });

    it('updates existing options', function () {
        ['importer' => $importer, 'optionRepo' => $repo, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('get')->with('opt')->andReturn(10);

        $repo->shouldReceive('update')->once();

        $importer->saveAttributeOptions([
            'update' => ['opt' => ['code' => 'opt']],
        ]);

        expect($importer->getUpdatedItemsCount())->toBe(1);
    });
});

describe('importBatch', function () {

    it('processes insert batch', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'optionRepo' => $repo, 'attrStorage' => $attrStorage] = makeImporter();

        $batch = makeAttributeOptionImportBatch(Import::ACTION_APPEND, [
            ['code' => 'opt', 'attribute_code' => 'color', 'locale' => 'en', 'label' => 'Option'],
        ]);

        $storage->shouldReceive('load')->with(['opt']);
        $storage->shouldReceive('has')->with('opt')->andReturn(false);
        $attrStorage->shouldReceive('get')->with('color')->andReturn(5);

        $model = new stdClass;
        $model->id = 2;

        $repo->shouldReceive('create')->andReturn($model);
        $storage->shouldReceive('set')->with('opt', 2);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();

        Event::assertDispatched('data_transfer.imports.batch.import.before');
        Event::assertDispatched('data_transfer.imports.batch.import.after');
    });

    it('processes delete batch', function () {
        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'optionRepo' => $repo] = makeImporter();

        $batch = makeAttributeOptionImportBatch(Import::ACTION_DELETE, [
            ['code' => 'opt'],
        ]);

        $storage->shouldReceive('load')->with(['opt']);
        $storage->shouldReceive('has')->with('opt')->andReturn(true);
        $storage->shouldReceive('get')->with('opt')->andReturn(9);

        $repo->shouldReceive('deleteWhere')->once()->with([['id', 'IN', [9]]]);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();
    });
});

describe('isAttributeOptionExist', function () {

    it('returns true if exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('opt')->andReturn(true);

        expect($importer->isAttributeOptionExist('opt'))->toBeTrue();
    });

    it('returns false if not exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('opt')->andReturn(false);

        expect($importer->isAttributeOptionExist('opt'))->toBeFalse();
    });
});
