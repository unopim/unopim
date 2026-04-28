<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\AttributeGroup;

use Illuminate\Support\Facades\Event;
use stdClass;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AttributeGroup\Importer;
use Webkul\DataTransfer\Helpers\Importers\AttributeGroup\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

/**
 * Helper to create importer with mocks
 */
function makeImporter(array $overrides = []): array
{
    $batchRepo = $overrides['batchRepo'] ?? mock(JobTrackBatchRepository::class);
    $groupRepo = $overrides['groupRepo'] ?? mock(AttributeGroupRepository::class);
    $storage = $overrides['storage'] ?? mock(Storage::class);
    $localeRepo = $overrides['localeRepo'] ?? mock(LocaleRepository::class);
    $errorHelper = $overrides['errorHelper'] ?? mock(Error::class);

    $localeRepo->shouldReceive('getActiveLocales')
        ->andReturn(collect([(object) ['code' => 'en']]));

    $storage->shouldReceive('init')->byDefault();
    $storage->shouldReceive('load')->byDefault();

    $errorHelper->shouldReceive('addErrorMessage')->byDefault();
    $errorHelper->shouldReceive('addError')->byDefault();
    $errorHelper->shouldReceive('addRowToSkip')->byDefault();
    $errorHelper->shouldReceive('isRowInvalid')->andReturn(false)->byDefault();

    $importer = new Importer($batchRepo, $groupRepo, $storage, $localeRepo);
    $importer->setErrorHelper($errorHelper);

    return compact('importer', 'batchRepo', 'groupRepo', 'storage', 'localeRepo', 'errorHelper');
}

function makeImport(string $action): JobTrackContract
{
    $import = mock(JobTrackContract::class)->makePartial();
    $import->action = $action;

    return $import;
}

function makeAttributeGroupImportBatch(string $action, array $data = []): JobTrackBatchContract
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

        $storage->shouldReceive('get')->with('group1')->andReturn(10);

        expect($importer->validateRow(['code' => 'group1'], 1))->toBeTrue();
    });

    it('fails when locale is missing', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'grp', 'locale' => ''], 1))->toBeFalse();
    });

    it('fails when locale is invalid', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'grp', 'locale' => 'zz'], 1))->toBeFalse();
    });

    it('passes valid insert row', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('group1')->andReturn(false);

        expect($importer->validateRow(['code' => 'group1', 'locale' => 'en'], 1))->toBeTrue();
    });

    it('passes update row when already exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->with('group1')->andReturn(true);

        expect($importer->validateRow(['code' => 'group1', 'locale' => 'en'], 1))->toBeTrue();
    });

    it('caches validated row', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $storage->shouldReceive('has')->once()->andReturn(false);

        $importer->validateRow(['code' => 'grp', 'locale' => 'en'], 1);
        $result = $importer->validateRow(['code' => 'grp', 'locale' => 'en'], 1);

        expect($result)->toBeTrue();
    });
});

describe('prepareAttributeGroups', function () {

    it('adds new group to insert', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('grp')->andReturn(false);

        $data = [];

        $importer->prepareAttributeGroups(
            ['code' => 'grp', 'locale' => 'en', 'name' => 'Group'],
            $data
        );

        expect($data)->toHaveKey('insert')
            ->and($data['insert'])->toHaveKey('grp');
    });

    it('adds existing group to update', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('grp')->andReturn(true);

        $data = [];

        $importer->prepareAttributeGroups(
            ['code' => 'grp', 'locale' => 'en', 'name' => 'Group'],
            $data
        );

        expect($data)->toHaveKey('update');
    });

    it('merges locales', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('grp')->andReturn(false);

        $data = [];

        $importer->prepareAttributeGroups(['code' => 'grp', 'locale' => 'en', 'name' => 'Group'], $data);
        $importer->prepareAttributeGroups(['code' => 'grp', 'locale' => 'fr', 'name' => 'Groupe'], $data);

        expect($data['insert']['grp'])->toHaveKeys(['en', 'fr']);
    });
});

describe('saveAttributeGroups', function () {

    it('creates new groups', function () {
        ['importer' => $importer, 'groupRepo' => $repo, 'storage' => $storage] = makeImporter();

        $model = new stdClass;
        $model->id = 5;

        $repo->shouldReceive('create')->once()->andReturn($model);
        $storage->shouldReceive('set')->with('grp', 5);

        $importer->saveAttributeGroups([
            'insert' => ['grp' => ['code' => 'grp']],
        ]);

        expect($importer->getCreatedItemsCount())->toBe(1);
    });

    it('updates existing groups', function () {
        ['importer' => $importer, 'groupRepo' => $repo, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('get')->with('grp')->andReturn(10);

        $repo->shouldReceive('update')->once();

        $importer->saveAttributeGroups([
            'update' => ['grp' => ['code' => 'grp']],
        ]);

        expect($importer->getUpdatedItemsCount())->toBe(1);
    });
});

describe('importBatch', function () {

    it('processes insert batch', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'groupRepo' => $repo] = makeImporter();

        $batch = makeAttributeGroupImportBatch(Import::ACTION_APPEND, [
            ['code' => 'grp', 'locale' => 'en', 'name' => 'Group'],
        ]);

        $storage->shouldReceive('load')->with(['grp']);
        $storage->shouldReceive('has')->with('grp')->andReturn(false);

        $model = new stdClass;
        $model->id = 2;

        $repo->shouldReceive('create')->andReturn($model);
        $storage->shouldReceive('set')->with('grp', 2);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();

        Event::assertDispatched('data_transfer.imports.batch.import.before');
        Event::assertDispatched('data_transfer.imports.batch.import.after');
    });

    it('processes delete batch', function () {
        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'groupRepo' => $repo] = makeImporter();

        $batch = makeAttributeGroupImportBatch(Import::ACTION_DELETE, [
            ['code' => 'grp'],
        ]);

        $storage->shouldReceive('load')->with(['grp']);
        $storage->shouldReceive('has')->with('grp')->andReturn(true);
        $storage->shouldReceive('get')->with('grp')->andReturn(9);

        $repo->shouldReceive('deleteWhere')->once()->with([['id', 'IN', [9]]]);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();
    });
});

describe('isAttributeGroupExist', function () {

    it('returns true if exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('grp')->andReturn(true);

        expect($importer->isAttributeGroupExist('grp'))->toBeTrue();
    });

    it('returns false if not exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('grp')->andReturn(false);

        expect($importer->isAttributeGroupExist('grp'))->toBeFalse();
    });
});
