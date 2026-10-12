<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\AttributeFamily;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use stdClass;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer;
use Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function makeImporter(array $overrides = []): array
{
    $batchRepo = $overrides['batchRepo'] ?? mock(JobTrackBatchRepository::class);
    $familyRepo = $overrides['familyRepo'] ?? mock(AttributeFamilyRepository::class);
    $groupRepo = $overrides['groupRepo'] ?? mock(AttributeGroupRepository::class);
    $attrRepo = $overrides['attrRepo'] ?? mock(AttributeRepository::class);
    $channelRepo = $overrides['channelRepo'] ?? mock(ChannelRepository::class);
    $storage = $overrides['storage'] ?? mock(Storage::class);
    $localeRepo = $overrides['localeRepo'] ?? mock(LocaleRepository::class);
    $errorHelper = $overrides['errorHelper'] ?? mock(Error::class);

    $localeRepo->shouldReceive('getActiveLocales')
        ->andReturn(collect([(object) ['code' => 'en']]));

    $groupRepo->shouldReceive('query->select->get->pluck->toArray')
        ->andReturn(['general' => 1]);

    $attrRepo->shouldReceive('query->select->get->pluck->toArray')
        ->andReturn(['sku' => 10]);

    $channelRepo->shouldReceive('all->pluck->toArray')
        ->andReturn(['ecommerce' => 5]);

    $storage->shouldReceive('init')->byDefault();
    $storage->shouldReceive('load')->byDefault();
    $storage->shouldReceive('has')->byDefault()->andReturn(false);

    $errorHelper->shouldReceive('addErrorMessage')->byDefault();
    $errorHelper->shouldReceive('addError')->byDefault();
    $errorHelper->shouldReceive('addRowToSkip')->byDefault();
    $errorHelper->shouldReceive('isRowInvalid')->andReturn(false)->byDefault();

    $importer = new Importer(
        $batchRepo,
        $familyRepo,
        $groupRepo,
        $attrRepo,
        $channelRepo,
        $storage,
        $localeRepo
    );

    $importer->setErrorHelper($errorHelper);

    return compact(
        'importer',
        'familyRepo',
        'groupRepo',
        'attrRepo',
        'channelRepo',
        'storage',
        'batchRepo',
        'errorHelper'
    );
}

function makeImport(string $action): JobTrackContract
{
    $import = mock(JobTrackContract::class)->makePartial();
    $import->action = $action;

    return $import;
}

function makeAttributeFamilyImportBatch(string $action, array $data): JobTrackBatchContract
{
    $jobTrack = mock(JobTrackContract::class)->makePartial();
    $jobTrack->action = $action;

    $batch = mock(JobTrackBatchContract::class)->makePartial();
    $batch->jobTrack = $jobTrack;
    $batch->data = $data;
    $batch->id = 1;

    return $batch;
}

/*
|--------------------------------------------------------------------------
| validateRow
|--------------------------------------------------------------------------
*/

describe('validateRow', function () {

    it('fails when deleting non-existing family', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('missing')->andReturn(null);

        expect($importer->validateRow(['code' => 'missing'], 1))->toBeFalse();
    });

    it('passes delete when family exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_DELETE));

        $storage->shouldReceive('get')->with('default')->andReturn(1);

        expect($importer->validateRow(['code' => 'default'], 1))->toBeTrue();
    });

    it('fails when locale is invalid', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow([
            'code'   => 'default',
            'locale' => 'xx',
        ], 1))->toBeFalse();
    });

    it('fails when attribute group is invalid', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->andReturn(true);

        expect($importer->validateRow([
            'code'            => 'default',
            'locale'          => 'en',
            'attribute_group' => 'invalid',
        ], 1))->toBeFalse();
    });

    it('fails when attribute is invalid', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->andReturn(true);

        expect($importer->validateRow([
            'code'       => 'default',
            'locale'     => 'en',
            'attributes' => 'invalid',
        ], 1))->toBeFalse();
    });

    it('fails when channel is invalid', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        $errorHelper->shouldReceive('isRowInvalid')->andReturn(true);

        expect($importer->validateRow([
            'code'         => 'default',
            'locale'       => 'en',
            'completeness' => 'invalid_channel',
        ], 1))->toBeFalse();
    });

    it('passes valid row', function () {
        ['importer' => $importer] = makeImporter();

        $importer->setImport(makeImport(Import::ACTION_APPEND));

        expect($importer->validateRow([
            'code'            => 'default',
            'locale'          => 'en',
            'attribute_group' => 'general',
            'attributes'      => 'sku',
            'completeness'    => 'ecommerce',
        ], 1))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| prepareAttributeFamilies
|--------------------------------------------------------------------------
*/

describe('prepareAttributeFamilies', function () {

    it('merges multiple rows correctly', function () {
        ['importer' => $importer] = makeImporter();

        $data = [];

        $importer->prepareAttributeFamilies([
            'code'            => 'default',
            'locale'          => 'en',
            'name'            => 'Default',
            'attribute_group' => 'general',
            'attributes'      => 'sku',
            'completeness'    => 'ecommerce',
        ], $data);

        expect($data['insert']['default'])->toHaveKey('attribute_groups');
    });
});

/*
|--------------------------------------------------------------------------
| saveAttributeFamilies
|--------------------------------------------------------------------------
*/

describe('saveAttributeFamilies', function () {

    it('creates new family', function () {
        ['importer' => $importer, 'familyRepo' => $repo, 'storage' => $storage] = makeImporter();

        DB::shouldReceive('table->where->delete');
        DB::shouldReceive('table->insert');

        $model = new stdClass;
        $model->id = 10;

        $repo->shouldReceive('create')->andReturn($model);
        $storage->shouldReceive('set')->with('default', 10);

        $importer->saveAttributeFamilies([
            'insert' => [
                'default' => [
                    'code'             => 'default',
                    'translations'     => ['en' => 'Default'],
                    'attribute_groups' => [],
                ],
            ],
        ]);

        expect($importer->getCreatedItemsCount())->toBe(1);
    });

    it('updates existing family', function () {
        ['importer' => $importer, 'familyRepo' => $repo, 'storage' => $storage] = makeImporter();

        DB::shouldReceive('table->where->delete');
        DB::shouldReceive('table->insert');

        $storage->shouldReceive('get')->with('default')->andReturn(1);

        $repo->shouldReceive('update')->once();

        $importer->saveAttributeFamilies([
            'update' => [
                'default' => [
                    'code'             => 'default',
                    'translations'     => ['en' => 'Default'],
                    'attribute_groups' => [],
                ],
            ],
        ]);

        expect($importer->getUpdatedItemsCount())->toBe(1);
    });
});

/*
|--------------------------------------------------------------------------
| importBatch
|--------------------------------------------------------------------------
*/

describe('importBatch', function () {

    it('processes insert batch', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo] = makeImporter();

        $batch = makeAttributeFamilyImportBatch(Import::ACTION_APPEND, []);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();

        Event::assertDispatched('data_transfer.imports.batch.import.before');
        Event::assertDispatched('data_transfer.imports.batch.import.after');
    });

    it('processes delete batch', function () {
        ['importer' => $importer, 'batchRepo' => $batchRepo] = makeImporter();

        $batch = makeAttributeFamilyImportBatch(Import::ACTION_DELETE, []);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();
    });
});

/*
|--------------------------------------------------------------------------
| helper
|--------------------------------------------------------------------------
*/

describe('isAttributeFamilyExist', function () {

    it('returns true when exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('default')->andReturn(true);

        expect($importer->isAttributeFamilyExist('default'))->toBeTrue();
    });

    it('returns false when not exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeImporter();

        $storage->shouldReceive('has')->with('missing')->andReturn(false);

        expect($importer->isAttributeFamilyExist('missing'))->toBeFalse();
    });
});
