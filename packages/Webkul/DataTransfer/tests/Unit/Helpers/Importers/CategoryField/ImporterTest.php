<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\CategoryField;

use Illuminate\Support\Facades\Event;
use stdClass;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\CategoryField\Importer;
use Webkul\DataTransfer\Helpers\Importers\CategoryField\Storage;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

// ─── Shared helpers ──────────────────────────────────────────────────────────

/**
 * Build an Importer with Mockery mocks.
 */
function makeCategoryFieldImporter(array $overrides = []): array
{
    $batchRepo = $overrides['batchRepo'] ?? mock(JobTrackBatchRepository::class);
    $fieldRepo = $overrides['fieldRepo'] ?? mock(CategoryFieldRepository::class);
    $storage = $overrides['storage'] ?? mock(Storage::class);
    $localeRepo = $overrides['localeRepo'] ?? mock(LocaleRepository::class);
    $errorHelper = $overrides['errorHelper'] ?? mock(Error::class);

    $localeRepo->shouldReceive('getActiveLocales')
        ->andReturn(collect([(object) ['code' => 'en_US']]));

    $storage->shouldReceive('init')->byDefault();
    $storage->shouldReceive('load')->byDefault();

    $errorHelper->shouldReceive('addErrorMessage')->byDefault();
    $errorHelper->shouldReceive('addError')->byDefault();
    $errorHelper->shouldReceive('addRowToSkip')->byDefault();
    $errorHelper->shouldReceive('isRowInvalid')->andReturn(false)->byDefault();

    $importer = new Importer($batchRepo, $fieldRepo, $storage, $localeRepo);
    $importer->setErrorHelper($errorHelper);

    return compact('importer', 'batchRepo', 'fieldRepo', 'storage', 'localeRepo', 'errorHelper');
}

function makeCFImport(string $action): JobTrackContract
{
    $import = mock(JobTrackContract::class)->makePartial();
    $import->action = $action;

    return $import;
}

function makeCFBatch(string $action, array $data = []): JobTrackBatchContract
{
    $jobTrack = mock(JobTrackContract::class)->makePartial();
    $jobTrack->action = $action;

    $batch = mock(JobTrackBatchContract::class)->makePartial();
    $batch->jobTrack = $jobTrack;
    $batch->data = $data;
    $batch->id = 1;

    return $batch;
}

// ─── validateRow ─────────────────────────────────────────────────────────────

describe('validateRow', function () {

    it('fails when deleting non-existing code', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_DELETE));
        $storage->shouldReceive('get')->with('missing')->andReturn(null);

        expect($importer->validateRow(['code' => 'missing'], 1))->toBeFalse();
    });

    it('passes delete when code exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_DELETE));
        $storage->shouldReceive('get')->with('description')->andReturn(5);

        expect($importer->validateRow(['code' => 'description'], 1))->toBeTrue();
    });

    it('blocks delete for the protected "name" field', function () {
        ['importer' => $importer, 'storage' => $storage, 'errorHelper' => $errorHelper] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_DELETE));
        $storage->shouldReceive('get')->with('name')->andReturn(1);
        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'name'], 1))->toBeFalse();
    });

    it('fails when locale is empty', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_APPEND));
        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'desc', 'locale' => ''], 1))->toBeFalse();
    });

    it('fails when locale is invalid', function () {
        ['importer' => $importer, 'errorHelper' => $errorHelper] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_APPEND));
        $errorHelper->shouldReceive('isRowInvalid')->with(1)->andReturn(true);

        expect($importer->validateRow(['code' => 'desc', 'locale' => 'zz_ZZ'], 1))->toBeFalse();
    });

    it('passes valid insert row', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_APPEND));
        $storage->shouldReceive('has')->with('desc')->andReturn(false);

        expect($importer->validateRow(['code' => 'desc', 'locale' => 'en_US', 'type' => 'text'], 1))->toBeTrue();
    });

    it('passes update row when code already exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_APPEND));
        $storage->shouldReceive('has')->with('desc')->andReturn(true);

        expect($importer->validateRow(['code' => 'desc', 'locale' => 'en_US'], 1))->toBeTrue();
    });

    it('caches validated row result', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $importer->setImport(makeCFImport(Import::ACTION_APPEND));
        $storage->shouldReceive('has')->once()->andReturn(false);

        $importer->validateRow(['code' => 'desc', 'locale' => 'en_US', 'type' => 'text'], 1);
        $result = $importer->validateRow(['code' => 'desc', 'locale' => 'en_US', 'type' => 'text'], 1);

        expect($result)->toBeTrue();
    });
});

// ─── prepareCategoryFields ───────────────────────────────────────────────────

describe('prepareCategoryFields', function () {

    it('adds new field to insert bucket', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('desc')->andReturn(false);

        $data = [];
        $importer->prepareCategoryFields(
            ['code' => 'desc', 'locale' => 'en_US', 'name' => 'Description', 'type' => 'text'],
            $data
        );

        expect($data)->toHaveKey('insert')
            ->and($data['insert'])->toHaveKey('desc');
    });

    it('adds existing field to update bucket', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('desc')->andReturn(true);

        $data = [];
        $importer->prepareCategoryFields(
            ['code' => 'desc', 'locale' => 'en_US', 'name' => 'Description'],
            $data
        );

        expect($data)->toHaveKey('update');
    });

    it('merges multiple locale rows for the same code', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('desc')->andReturn(false);

        $data = [];
        $importer->prepareCategoryFields(['code' => 'desc', 'locale' => 'en_US', 'name' => 'Description'], $data);
        $importer->prepareCategoryFields(['code' => 'desc', 'locale' => 'fr_FR', 'name' => 'Description FR'], $data);

        expect($data['insert']['desc'])->toHaveKeys(['en_US', 'fr_FR']);
    });

    it('keeps code as a scalar when merging locales', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('desc')->andReturn(false);

        $data = [];
        $importer->prepareCategoryFields(['code' => 'desc', 'locale' => 'en_US', 'name' => 'Description'], $data);
        $importer->prepareCategoryFields(['code' => 'desc', 'locale' => 'fr_FR', 'name' => 'Description FR'], $data);

        expect($data['insert']['desc']['code'])->toBe('desc');
    });

    it('casts boolean fields correctly', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('req')->andReturn(false);

        $data = [];
        $importer->prepareCategoryFields([
            'code'        => 'req',
            'locale'      => 'en_US',
            'is_required' => '1',
            'is_unique'   => '0',
            'status'      => '1',
        ], $data);

        expect($data['insert']['req']['is_required'])->toBe(1)
            ->and($data['insert']['req']['is_unique'])->toBe(0)
            ->and($data['insert']['req']['status'])->toBe(1);
    });

    it('casts position to integer', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('pf')->andReturn(false);

        $data = [];
        $importer->prepareCategoryFields([
            'code'     => 'pf',
            'locale'   => 'en_US',
            'position' => '3',
        ], $data);

        expect($data['insert']['pf']['position'])->toBe(3);
    });
});

// ─── saveCategoryFields ───────────────────────────────────────────────────────

describe('saveCategoryFields', function () {

    it('creates new category fields', function () {
        ['importer' => $importer, 'fieldRepo' => $repo, 'storage' => $storage] = makeCategoryFieldImporter();

        $model = new stdClass;
        $model->id = 7;

        $repo->shouldReceive('create')->once()->andReturn($model);
        $storage->shouldReceive('set')->with('desc', 7);

        $importer->saveCategoryFields([
            'insert' => ['desc' => ['code' => 'desc']],
        ]);

        expect($importer->getCreatedItemsCount())->toBe(1);
    });

    it('updates existing category fields', function () {
        ['importer' => $importer, 'fieldRepo' => $repo, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('get')->with('desc')->andReturn(12);
        $repo->shouldReceive('update')->once();

        $importer->saveCategoryFields([
            'update' => ['desc' => ['code' => 'desc']],
        ]);

        expect($importer->getUpdatedItemsCount())->toBe(1);
    });
});

// ─── importBatch ─────────────────────────────────────────────────────────────

describe('importBatch', function () {

    it('processes insert batch and dispatches events', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'fieldRepo' => $repo]
            = makeCategoryFieldImporter();

        $batch = makeCFBatch(Import::ACTION_APPEND, [
            ['code' => 'desc', 'locale' => 'en_US', 'name' => 'Description', 'type' => 'text'],
        ]);

        $storage->shouldReceive('load')->with(['desc']);
        $storage->shouldReceive('has')->with('desc')->andReturn(false);

        $model = new stdClass;
        $model->id = 3;

        $repo->shouldReceive('create')->andReturn($model);
        $storage->shouldReceive('set')->with('desc', 3);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();

        Event::assertDispatched('data_transfer.imports.batch.import.before');
        Event::assertDispatched('data_transfer.imports.batch.import.after');
    });

    it('processes delete batch', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'fieldRepo' => $repo]
            = makeCategoryFieldImporter();

        $batch = makeCFBatch(Import::ACTION_DELETE, [
            ['code' => 'desc'],
        ]);

        $storage->shouldReceive('load')->with(['desc']);
        $storage->shouldReceive('has')->with('desc')->andReturn(true);
        $storage->shouldReceive('get')->with('desc')->andReturn(11);

        $repo->shouldReceive('deleteWhere')->once()->with([['id', 'IN', [11]]]);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        expect($importer->importBatch($batch))->toBeTrue();
    });

    it('skips protected "name" field during delete', function () {
        Event::fake();

        ['importer' => $importer, 'batchRepo' => $batchRepo, 'storage' => $storage, 'fieldRepo' => $repo]
            = makeCategoryFieldImporter();

        $batch = makeCFBatch(Import::ACTION_DELETE, [
            ['code' => 'name'],
        ]);

        $storage->shouldReceive('load')->with(['name']);
        $storage->shouldReceive('has')->with('name')->andReturn(true);
        $storage->shouldReceive('get')->with('name')->andReturn(1)->byDefault();

        // deleteWhere should NOT be called with any ids (name is protected)
        $repo->shouldReceive('deleteWhere')->once()->with([['id', 'IN', []]]);

        $batchRepo->shouldReceive('update')->andReturn($batch);

        $importer->importBatch($batch);
    });
});

// ─── isCategoryFieldExist ────────────────────────────────────────────────────

describe('isCategoryFieldExist', function () {

    it('returns true when field exists', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('desc')->andReturn(true);

        expect($importer->isCategoryFieldExist('desc'))->toBeTrue();
    });

    it('returns false when field does not exist', function () {
        ['importer' => $importer, 'storage' => $storage] = makeCategoryFieldImporter();

        $storage->shouldReceive('has')->with('missing')->andReturn(false);

        expect($importer->isCategoryFieldExist('missing'))->toBeFalse();
    });
});
